<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Equipment\CreateEquipmentService;
use App\Application\UseCase\Equipment\GetEquipmentService;
use App\Application\UseCase\Equipment\SoftDeleteEquipmentService;
use App\Application\UseCase\Equipment\UpdateEquipmentService;
use App\Domain\Exception\EquipmentHasOpenRentals;
use App\Domain\Exception\ModelCodeTaken;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\FixedClock;
use Tests\Support\Fake\InMemoryCategories;
use Tests\Support\Fake\InMemoryEquipment;
use Tests\Support\Fake\SequentialIds;

/** T-08 - US-03 and US-04 against doubles. */
final class EquipmentCatalogServiceTest extends TestCase
{
    private const CATEGORY_ID = '11111111-1111-4111-8111-111111111111';

    private InMemoryEquipment $equipment;

    private CreateEquipmentService $create;

    private UpdateEquipmentService $update;

    private GetEquipmentService $get;

    private SoftDeleteEquipmentService $softDelete;

    protected function setUp(): void
    {
        $this->equipment = new InMemoryEquipment();

        $categories = new InMemoryCategories();
        $categories->seed(self::CATEGORY_ID, 'Cámaras');

        $this->create = new CreateEquipmentService($this->equipment, $categories, new SequentialIds());
        $this->update = new UpdateEquipmentService($this->equipment, $categories);
        $this->get = new GetEquipmentService($this->equipment);
        $this->softDelete = new SoftDeleteEquipmentService(
            $this->equipment,
            new FixedClock(new DateTimeImmutable('2026-11-13T10:00:00Z')),
        );
    }

    public function test_creating_equipment_starts_available_equal_to_the_total(): void
    {
        $equipment = $this->create->handle('Sony FX3', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 4);

        $this->assertSame(4, $equipment->totalUnits);
        $this->assertSame(4, $equipment->availableUnits);
        $this->assertNull($equipment->photoKey);
    }

    public function test_a_duplicate_model_code_is_rejected(): void
    {
        $this->create->handle('Sony FX3', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 2);

        $this->expectException(ModelCodeTaken::class);

        $this->create->handle('Otro cuerpo', 'Sony', 'fx3-body', self::CATEGORY_ID, 1);
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->create->handle('Sony FX3', 'Sony', 'FX3-BODY', 'category-404', 1);
    }

    public function test_reading_unknown_equipment_is_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->get->handle('equipment-404');
    }

    public function test_updating_keeps_the_model_code(): void
    {
        $created = $this->create->handle('Sony FX3', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 2);

        $updated = $this->update->handle($created->id, 'Sony FX6', 'Sony', self::CATEGORY_ID, 3);

        $this->assertSame('Sony FX6', $updated->name);
        $this->assertSame('FX3-BODY', $updated->modelCode->value);
        $this->assertSame(3, $updated->availableUnits);
    }

    public function test_soft_deleting_removes_it_from_the_catalogue(): void
    {
        $created = $this->create->handle('Sony FX3', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 1);

        $this->softDelete->handle($created->id);

        $this->expectException(ResourceNotFound::class);

        $this->get->handle($created->id);
    }

    public function test_equipment_with_open_rentals_cannot_be_soft_deleted(): void
    {
        $created = $this->create->handle('Sony FX3', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 1);
        $this->equipment->withOpenRentals($created->id);

        $this->expectException(EquipmentHasOpenRentals::class);

        $this->softDelete->handle($created->id);
    }

    /** ADR-003 - a retired code is free again, because the index is partial. */
    public function test_a_retired_model_code_can_be_reused(): void
    {
        $created = $this->create->handle('Sony FX3', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 1);
        $this->softDelete->handle($created->id);

        $replacement = $this->create->handle('Sony FX3 nuevo', 'Sony', 'FX3-BODY', self::CATEGORY_ID, 2);

        $this->assertSame('FX3-BODY', $replacement->modelCode->value);
        $this->assertNotSame($created->id, $replacement->id);
    }
}
