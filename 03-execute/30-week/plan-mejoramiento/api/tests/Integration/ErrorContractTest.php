<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Testing\TestResponse;

/**
 * T-16 and T-26 - Every business code in the contract, over HTTP.
 *
 * The contract names fourteen codes and promises that each one arrives with
 * one status in the same body. A code nobody has ever seen the API return is
 * a code that may not be reachable at all, which is exactly the kind of gap
 * a document hides.
 *
 * INTERNAL_ERROR is not here on purpose: it answers a defect, and a test that
 * could trigger it would be a test of a bug.
 */
final class ErrorContractTest extends IntegrationTestCase
{
    public function test_a_wrong_password_answers_invalid_credentials(): void
    {
        $this->signInAsAdmin();

        $this->assertRefusal(
            $this->postJson('/api/v1/auth/login', ['username' => 'root', 'password' => 'wrong']),
            401,
            'INVALID_CREDENTIALS',
        );
    }

    public function test_an_advisor_on_an_admin_route_answers_forbidden_role(): void
    {
        $tokens = $this->signInAsBothRoles();

        $this->assertRefusal(
            $this->getJson(
                '/api/v1/reports/most-requested?from=2026-01-01T00:00:00Z&to=2030-01-01T00:00:00Z',
                $this->bearer($tokens['advisor']),
            ),
            403,
            'FORBIDDEN_ROLE',
        );
    }

    public function test_the_report_still_answers_the_administrator(): void
    {
        $tokens = $this->signInAsBothRoles();

        $this->getJson(
            '/api/v1/reports/most-requested?from=2026-01-01T00:00:00Z&to=2030-01-01T00:00:00Z',
            $this->bearer($tokens['admin']),
        )->assertStatus(200);
    }

    public function test_an_unknown_equipment_answers_not_found(): void
    {
        $token = $this->signInAsAdmin();

        $this->assertRefusal(
            $this->getJson('/api/v1/equipment/11111111-2222-4333-8444-555555555555', $this->bearer($token)),
            404,
            'NOT_FOUND',
        );
    }

    public function test_a_repeated_username_answers_username_taken(): void
    {
        $token = $this->signInAsAdmin();
        $body = ['username' => 'advisor-two', 'password' => 'a-password', 'role' => 'advisor'];

        $this->postJson('/api/v1/users', $body, $this->bearer($token))->assertStatus(201);

        $this->assertRefusal(
            $this->postJson('/api/v1/users', $body, $this->bearer($token)),
            409,
            'USERNAME_TAKEN',
        );
    }

    public function test_asking_for_the_admin_role_answers_validation_error(): void
    {
        $token = $this->signInAsAdmin();

        // AC-02.3: the deployment provisions that role and the API never does.
        $this->assertRefusal(
            $this->postJson('/api/v1/users', [
                'username' => 'second-admin',
                'password' => 'a-password',
                'role' => 'admin',
            ], $this->bearer($token)),
            422,
            'VALIDATION_ERROR',
        );
    }

    public function test_a_repeated_model_code_answers_model_code_taken(): void
    {
        $token = $this->signInAsAdmin();
        $this->createEquipment($token, 'CAM-001');

        $this->assertRefusal(
            $this->postJson('/api/v1/equipment', [
                'name' => 'Otra cámara',
                'brand' => 'Marca dos',
                'model_code' => 'CAM-001',
                'category_id' => self::CAMERAS,
                'total_units' => 1,
            ], $this->bearer($token)),
            409,
            'MODEL_CODE_TAKEN',
        );
    }

    public function test_a_repeated_document_answers_document_taken(): void
    {
        $token = $this->signInAsAdmin();
        $this->createProducer($token, '1001');

        $this->assertRefusal(
            $this->postJson('/api/v1/producers', [
                'full_name' => 'Otra productora',
                'document_number' => '1001',
            ], $this->bearer($token)),
            409,
            'DOCUMENT_TAKEN',
        );
    }

    public function test_retiring_equipment_that_is_out_answers_equipment_has_open_rentals(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 2);
        $this->rent($token, $this->createProducer($token, '1001'), [$equipment]);

        $this->assertRefusal(
            $this->deleteJson('/api/v1/equipment/'.$equipment, [], $this->bearer($token)),
            409,
            'EQUIPMENT_HAS_OPEN_RENTALS',
        );
    }

    public function test_renting_with_no_units_left_answers_equipment_not_available(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 1);
        $this->rent($token, $this->createProducer($token, '1001'), [$equipment]);

        $this->assertRefusal(
            $this->postJson('/api/v1/rentals', [
                'producer_id' => $this->createProducer($token, '1002'),
                'equipment_ids' => [$equipment],
            ], $this->bearer($token)),
            409,
            'EQUIPMENT_NOT_AVAILABLE',
        );
    }

    public function test_a_producer_holding_something_overdue_answers_producer_has_overdue(): void
    {
        $token = $this->signInAsAdmin();
        $producer = $this->createProducer($token, '1001');
        $rental = $this->rent($token, $producer, [$this->createEquipment($token, 'CAM-001', 2)]);

        // Five days on, the four day term has run out (BR-2, BR-3).
        $this->ageDueDateBy($rental['id'], 5);

        $this->assertRefusal(
            $this->postJson('/api/v1/rentals', [
                'producer_id' => $producer,
                'equipment_ids' => [$this->createEquipment($token, 'CAM-002', 2)],
            ], $this->bearer($token)),
            409,
            'PRODUCER_HAS_OVERDUE',
        );
    }

    public function test_returning_a_line_twice_answers_item_already_returned(): void
    {
        $token = $this->signInAsAdmin();
        $rental = $this->rent(
            $token,
            $this->createProducer($token, '1001'),
            [$this->createEquipment($token, 'CAM-001', 2)],
        );

        $path = '/api/v1/rentals/'.$rental['id'].'/items/'.$rental['items'][0]['id'].'/return';
        $this->postJson($path, [], $this->bearer($token))->assertStatus(200);

        $this->assertRefusal(
            $this->postJson($path, [], $this->bearer($token)),
            409,
            'ITEM_ALREADY_RETURNED',
        );
    }

    public function test_a_third_piece_of_equipment_answers_producer_limit_exceeded(): void
    {
        $token = $this->signInAsAdmin();
        $producer = $this->createProducer($token, '1001');
        $this->rent($token, $producer, [
            $this->createEquipment($token, 'CAM-001', 1),
            $this->createEquipment($token, 'CAM-002', 1),
        ]);

        $this->assertRefusal(
            $this->postJson('/api/v1/rentals', [
                'producer_id' => $producer,
                'equipment_ids' => [$this->createEquipment($token, 'CAM-003', 1)],
            ], $this->bearer($token)),
            422,
            'PRODUCER_LIMIT_EXCEEDED',
        );
    }

    public function test_the_same_equipment_twice_answers_duplicate_equipment_in_rental(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 5);

        $this->assertRefusal(
            $this->postJson('/api/v1/rentals', [
                'producer_id' => $this->createProducer($token, '1001'),
                'equipment_ids' => [$equipment, $equipment],
            ], $this->bearer($token)),
            422,
            'DUPLICATE_EQUIPMENT_IN_RENTAL',
        );
    }

    public function test_a_missing_field_answers_validation_error_naming_the_field(): void
    {
        $token = $this->signInAsAdmin();

        $response = $this->postJson('/api/v1/equipment', [
            'brand' => 'Marca uno',
            'model_code' => 'CAM-001',
            'category_id' => self::CAMERAS,
            'total_units' => 2,
        ], $this->bearer($token));

        $this->assertRefusal($response, 422, 'VALIDATION_ERROR');
        // The detail names the field, which is what lets a form mark it.
        $response->assertJsonPath('details.0.field', 'name');
    }

    public function test_an_inverted_date_range_answers_invalid_date_range(): void
    {
        $token = $this->signInAsAdmin();

        $this->assertRefusal(
            $this->getJson(
                '/api/v1/reports/most-requested?from=2030-01-01T00:00:00Z&to=2026-01-01T00:00:00Z',
                $this->bearer($token),
            ),
            422,
            'INVALID_DATE_RANGE',
        );
    }

    public function test_the_overdue_list_shows_the_aged_line(): void
    {
        $token = $this->signInAsAdmin();
        $rental = $this->rent(
            $token,
            $this->createProducer($token, '1001'),
            [$this->createEquipment($token, 'CAM-001', 2)],
        );

        $this->ageDueDateBy($rental['id'], 6);

        $response = $this->getJson('/api/v1/rentals/overdue', $this->bearer($token));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'items');
        // Six days past a due date that was already four days out: the count
        // is days late, not days held (AC-09.1).
        $response->assertJsonPath('items.0.days_overdue', 6);
        $response->assertJsonPath('items.0.producer_name', 'Productora 1001');
    }

    /** Every refusal carries the same three fields; only two of them vary. */
    private function assertRefusal(TestResponse $response, int $status, string $code): void
    {
        $response->assertStatus($status);
        $response->assertJsonPath('code', $code);
        $response->assertJsonStructure(['code', 'message', 'details']);
        // The message is for a person to read and is Spanish by article XI.
        self::assertNotSame('', (string) $response->json('message'));
    }

    /** @param list<string> $equipmentIds @return array<string, mixed> */
    private function rent(string $token, string $producerId, array $equipmentIds): array
    {
        $response = $this->postJson('/api/v1/rentals', [
            'producer_id' => $producerId,
            'equipment_ids' => $equipmentIds,
        ], $this->bearer($token));

        $response->assertStatus(201);

        return (array) $response->json();
    }

    private function createProducer(string $token, string $document): string
    {
        $response = $this->postJson('/api/v1/producers', [
            'full_name' => 'Productora '.$document,
            'document_number' => $document,
        ], $this->bearer($token));

        $response->assertStatus(201);

        return (string) $response->json('id');
    }

    private function createEquipment(string $token, string $modelCode, int $totalUnits = 2): string
    {
        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'Cámara réflex',
            'brand' => 'Marca uno',
            'model_code' => $modelCode,
            'category_id' => self::CAMERAS,
            'total_units' => $totalUnits,
        ], $this->bearer($token));

        $response->assertStatus(201);

        return (string) $response->json('id');
    }
}
