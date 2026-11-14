<?php

declare(strict_types=1);

namespace Tests\Integration;

/**
 * T-12 - The catalogue against the real engine.
 *
 * These are the assertions the fakes cannot make: the partial unique index on
 * the model code, the soft delete that hides a row from every read, and the
 * ILIKE search, which is a PostgreSQL behaviour and not an array filter.
 */
final class EquipmentCatalogTest extends IntegrationTestCase
{
    public function test_an_administrator_creates_equipment_and_the_server_sets_the_available_units(): void
    {
        $token = $this->signInAsAdmin();

        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'Cámara réflex',
            'brand' => 'Marca uno',
            'model_code' => 'CAM-001',
            'category_id' => self::CAMERAS,
            'total_units' => 3,
        ], $this->bearer($token));

        $response->assertStatus(201);
        // Not read from the request: a client cannot hand itself extra stock.
        $response->assertJsonPath('available_units', 3);
        $response->assertJsonPath('category.name', 'Cámaras');
    }

    public function test_a_repeated_model_code_is_refused_while_the_equipment_is_active(): void
    {
        $token = $this->signInAsAdmin();
        $this->createEquipment($token, 'CAM-001');

        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'Otra cámara',
            'brand' => 'Marca dos',
            'model_code' => 'CAM-001',
            'category_id' => self::CAMERAS,
            'total_units' => 1,
        ], $this->bearer($token));

        $response->assertStatus(409);
        $response->assertJsonPath('code', 'MODEL_CODE_TAKEN');
    }

    public function test_retiring_equipment_frees_its_model_code(): void
    {
        $token = $this->signInAsAdmin();
        $id = $this->createEquipment($token, 'CAM-001');

        $this->deleteJson('/api/v1/equipment/'.$id, [], $this->bearer($token))->assertStatus(204);

        // The unique index is partial, so the code comes back into play
        // (AC-03.2, ADR-003).
        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'Cámara nueva',
            'brand' => 'Marca dos',
            'model_code' => 'CAM-001',
            'category_id' => self::CAMERAS,
            'total_units' => 1,
        ], $this->bearer($token));

        $response->assertStatus(201);
    }

    public function test_retired_equipment_is_gone_from_the_detail_and_from_the_list(): void
    {
        $token = $this->signInAsAdmin();
        $id = $this->createEquipment($token, 'CAM-001');

        $this->deleteJson('/api/v1/equipment/'.$id, [], $this->bearer($token))->assertStatus(204);

        $this->getJson('/api/v1/equipment/'.$id, $this->bearer($token))->assertStatus(404);
        $this->getJson('/api/v1/equipment', $this->bearer($token))->assertJsonPath('total', 0);
    }

    public function test_the_search_matches_the_name_and_the_brand_without_regard_to_case(): void
    {
        $token = $this->signInAsAdmin();
        $this->createEquipment($token, 'CAM-001', 'Cámara réflex', 'Marca uno');
        $this->createEquipment($token, 'LEN-001', 'Lente 50mm', 'Otra marca', self::LENSES);

        $byName = $this->getJson('/api/v1/equipment?q=RÉFLEX', $this->bearer($token));
        $byName->assertJsonPath('total', 1);
        $byName->assertJsonPath('items.0.model_code', 'CAM-001');

        $byBrand = $this->getJson('/api/v1/equipment?q=otra', $this->bearer($token));
        $byBrand->assertJsonPath('total', 1);
        $byBrand->assertJsonPath('items.0.model_code', 'LEN-001');
    }

    public function test_the_list_can_be_narrowed_to_one_category(): void
    {
        $token = $this->signInAsAdmin();
        $this->createEquipment($token, 'CAM-001');
        $this->createEquipment($token, 'LEN-001', 'Lente 50mm', 'Marca uno', self::LENSES);

        $response = $this->getJson('/api/v1/equipment?category_id='.self::LENSES, $this->bearer($token));

        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('items.0.model_code', 'LEN-001');
    }

    public function test_a_page_size_over_the_maximum_is_capped_and_not_refused(): void
    {
        $token = $this->signInAsAdmin();
        $this->createEquipment($token, 'CAM-001');

        $response = $this->getJson('/api/v1/equipment?size=500', $this->bearer($token));

        $response->assertStatus(200);
        $response->assertJsonPath('size', 50);
    }

    private function createEquipment(
        string $token,
        string $modelCode,
        string $name = 'Cámara réflex',
        string $brand = 'Marca uno',
        string $categoryId = self::CAMERAS,
        int $totalUnits = 2,
    ): string {
        $response = $this->postJson('/api/v1/equipment', [
            'name' => $name,
            'brand' => $brand,
            'model_code' => $modelCode,
            'category_id' => $categoryId,
            'total_units' => $totalUnits,
        ], $this->bearer($token));

        $response->assertStatus(201);

        return (string) $response->json('id');
    }
}
