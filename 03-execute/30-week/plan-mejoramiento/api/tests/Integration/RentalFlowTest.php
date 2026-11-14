<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * T-12 - A rental from the producer to the report, over HTTP.
 *
 * What only the engine can prove here: the state is computed and never stored,
 * so the filters are SQL subqueries over the lines; the frozen name on a line
 * survives a rename of the catalogue entry; and the return is idempotent
 * because of a WHERE and not because of a flag in PHP.
 */
final class RentalFlowTest extends IntegrationTestCase
{
    public function test_a_rental_takes_a_unit_out_of_the_catalogue_and_the_return_puts_it_back(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 2);
        $producer = $this->createProducer($token, '1001');

        $rental = $this->postJson('/api/v1/rentals', [
            'producer_id' => $producer,
            'equipment_ids' => [$equipment],
        ], $this->bearer($token));

        $rental->assertStatus(201);
        $rental->assertJsonPath('status', 'open');

        $this->assertSame(1, $this->availableUnitsOf($token, $equipment));

        $itemId = (string) $rental->json('items.0.id');
        $returned = $this->postJson(
            '/api/v1/rentals/'.$rental->json('id').'/items/'.$itemId.'/return',
            [],
            $this->bearer($token),
        );

        $returned->assertStatus(200);
        $returned->assertJsonPath('status', 'closed');
        $this->assertSame(2, $this->availableUnitsOf($token, $equipment));
    }

    public function test_returning_the_same_line_twice_is_refused_and_does_not_restock_twice(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 2);
        $producer = $this->createProducer($token, '1001');
        $rental = $this->rent($token, $producer, [$equipment]);

        $path = '/api/v1/rentals/'.$rental['id'].'/items/'.$rental['items'][0]['id'].'/return';

        $this->postJson($path, [], $this->bearer($token))->assertStatus(200);

        $second = $this->postJson($path, [], $this->bearer($token));

        $second->assertStatus(409);
        $second->assertJsonPath('code', 'ITEM_ALREADY_RETURNED');
        // The guard is the WHERE returned_at is null, so the unit came back
        // once and not twice (AC-07.2).
        $this->assertSame(2, $this->availableUnitsOf($token, $equipment));
    }

    public function test_equipment_with_no_units_left_is_refused(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 1);
        $this->rent($token, $this->createProducer($token, '1001'), [$equipment]);

        $response = $this->postJson('/api/v1/rentals', [
            'producer_id' => $this->createProducer($token, '1002'),
            'equipment_ids' => [$equipment],
        ], $this->bearer($token));

        $response->assertStatus(409);
        $response->assertJsonPath('code', 'EQUIPMENT_NOT_AVAILABLE');
    }

    public function test_a_producer_cannot_hold_more_than_two_pieces_at_a_time(): void
    {
        $token = $this->signInAsAdmin();
        $producer = $this->createProducer($token, '1001');
        $this->rent($token, $producer, [
            $this->createEquipment($token, 'CAM-001', 1),
            $this->createEquipment($token, 'CAM-002', 1),
        ]);

        $response = $this->postJson('/api/v1/rentals', [
            'producer_id' => $producer,
            'equipment_ids' => [$this->createEquipment($token, 'CAM-003', 1)],
        ], $this->bearer($token));

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'PRODUCER_LIMIT_EXCEEDED');
    }

    public function test_the_same_equipment_twice_in_one_rental_is_refused(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 5);

        $response = $this->postJson('/api/v1/rentals', [
            'producer_id' => $this->createProducer($token, '1001'),
            'equipment_ids' => [$equipment, $equipment],
        ], $this->bearer($token));

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'DUPLICATE_EQUIPMENT_IN_RENTAL');
    }

    public function test_the_producer_detail_counts_the_equipment_still_out(): void
    {
        $token = $this->signInAsAdmin();
        $producer = $this->createProducer($token, '1001');
        $this->rent($token, $producer, [$this->createEquipment($token, 'CAM-001', 1)]);

        $response = $this->getJson('/api/v1/producers/'.$producer, $this->bearer($token));

        $response->assertJsonPath('open_rentals', 1);
    }

    public function test_the_rental_list_filters_by_a_state_nobody_stored(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 2);
        $rental = $this->rent($token, $this->createProducer($token, '1001'), [$equipment]);

        $this->getJson('/api/v1/rentals?status=open', $this->bearer($token))->assertJsonPath('total', 1);
        $this->getJson('/api/v1/rentals?status=closed', $this->bearer($token))->assertJsonPath('total', 0);

        $this->postJson(
            '/api/v1/rentals/'.$rental['id'].'/items/'.$rental['items'][0]['id'].'/return',
            [],
            $this->bearer($token),
        )->assertStatus(200);

        // Nothing was written to a status column, because there is none: the
        // filter is an EXISTS over the lines (article VII).
        $this->getJson('/api/v1/rentals?status=open', $this->bearer($token))->assertJsonPath('total', 0);
        $this->getJson('/api/v1/rentals?status=closed', $this->bearer($token))->assertJsonPath('total', 1);
    }

    public function test_a_renamed_equipment_keeps_its_old_name_on_the_line_and_in_the_report(): void
    {
        $token = $this->signInAsAdmin();
        $equipment = $this->createEquipment($token, 'CAM-001', 2);
        $this->rent($token, $this->createProducer($token, '1001'), [$equipment]);

        $this->putJson('/api/v1/equipment/'.$equipment, [
            'name' => 'Cámara renombrada',
            'brand' => 'Marca uno',
            'category_id' => self::CAMERAS,
            'total_units' => 2,
        ], $this->bearer($token))->assertStatus(200);

        // The line froze the name at the instant of the rental (article VIII,
        // ADR-004), so neither the history nor the report follows the rename.
        $report = $this->getJson(
            '/api/v1/reports/most-requested?from=2026-01-01T00:00:00Z&to=2030-01-01T00:00:00Z',
            $this->bearer($token),
        );

        $report->assertStatus(200);
        $report->assertJsonPath('items.0.equipment_name', 'Cámara réflex');
        $report->assertJsonPath('items.0.times_rented', 1);
    }

    public function test_nothing_is_overdue_while_the_window_is_open(): void
    {
        $token = $this->signInAsAdmin();
        $this->rent($token, $this->createProducer($token, '1001'), [
            $this->createEquipment($token, 'CAM-001', 1),
        ]);

        $response = $this->getJson('/api/v1/rentals/overdue', $this->bearer($token));

        $response->assertStatus(200);
        // The window is four days, so a rental made now is not late. Staging a
        // late line would mean writing a due date the API cannot produce.
        $response->assertJsonCount(0, 'items');
    }

    /**
     * @param  list<string>  $equipmentIds
     * @return array<string, mixed>
     */
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

    private function createEquipment(string $token, string $modelCode, int $totalUnits): string
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

    private function availableUnitsOf(string $token, string $equipmentId): int
    {
        return (int) $this->getJson('/api/v1/equipment/'.$equipmentId, $this->bearer($token))
            ->json('available_units');
    }
}
