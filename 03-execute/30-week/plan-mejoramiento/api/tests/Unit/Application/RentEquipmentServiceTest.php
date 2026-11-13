<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Rental\RentEquipmentService;
use App\Domain\Equipment\Equipment;
use App\Domain\Exception\DuplicateEquipmentInRental;
use App\Domain\Exception\EquipmentNotAvailable;
use App\Domain\Exception\ProducerHasOverdue;
use App\Domain\Exception\ProducerLimitExceeded;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Producer\Producer;
use App\Domain\Rental\ProducerBorrowingLimit;
use App\Domain\Shared\DocumentNumber;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\Quantity;
use App\Domain\Shared\RentalPolicy;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\FixedClock;
use Tests\Support\Fake\InMemoryCategories;
use Tests\Support\Fake\InMemoryEquipment;
use Tests\Support\Fake\InMemoryProducers;
use Tests\Support\Fake\InMemoryRentals;
use Tests\Support\Fake\InstantTransactions;
use Tests\Support\Fake\SequentialIds;

/** T-09 - US-06 in full, against doubles and with no database. */
final class RentEquipmentServiceTest extends TestCase
{
    private const CATEGORY_ID = '11111111-1111-4111-8111-111111111111';

    private InMemoryEquipment $equipment;

    private InMemoryProducers $producers;

    private InMemoryRentals $rentals;

    private RentEquipmentService $service;

    protected function setUp(): void
    {
        $this->equipment = new InMemoryEquipment();
        $this->producers = new InMemoryProducers();
        $this->rentals = new InMemoryRentals();

        $categories = new InMemoryCategories();
        $categories->seed(self::CATEGORY_ID, 'Cámaras');

        $this->producers->add(Producer::register('producer-1', 'Laura Ortiz', DocumentNumber::fromString('1098765432')));
        $this->equipment->seed($this->anEquipment('equipment-1', 'Sony FX3', 'FX3-BODY', 2));
        $this->equipment->seed($this->anEquipment('equipment-2', 'DJI Mini 4 Pro', 'DJI-MINI4PRO', 1));

        $policy = new RentalPolicy();

        $this->service = new RentEquipmentService(
            $this->rentals,
            $this->equipment,
            $this->producers,
            $categories,
            new InstantTransactions(),
            new FixedClock(new DateTimeImmutable('2026-11-13T14:15:00Z')),
            new SequentialIds(),
            $policy,
            new ProducerBorrowingLimit($policy),
        );
    }

    public function test_it_rents_and_takes_one_unit_per_line(): void
    {
        $rental = $this->service->handle('producer-1', ['equipment-1', 'equipment-2'], 'user-1');

        $this->assertCount(2, $rental->items);
        $this->assertSame(1, $this->equipment->stored('equipment-1')->availableUnits);
        $this->assertSame(0, $this->equipment->stored('equipment-2')->availableUnits);
        $this->assertNotNull($this->rentals->find($rental->id));
    }

    public function test_it_freezes_the_catalogue_values_on_the_line(): void
    {
        $item = $this->service->handle('producer-1', ['equipment-1'], 'user-1')->items[0];

        $this->assertSame('Sony FX3', $item->equipmentName);
        $this->assertSame('FX3-BODY', $item->modelCode->value);
        $this->assertSame('Cámaras', $item->categoryName);
        $this->assertSame('2026-11-17T14:15:00+00:00', $item->dueAt->format('c'));
    }

    public function test_repeated_equipment_is_rejected_without_touching_inventory(): void
    {
        try {
            $this->service->handle('producer-1', ['equipment-1', 'equipment-1'], 'user-1');
            $this->fail('Expected DuplicateEquipmentInRental.');
        } catch (DuplicateEquipmentInRental) {
            $this->assertSame(2, $this->equipment->stored('equipment-1')->availableUnits);
        }
    }

    public function test_with_no_units_it_is_a_conflict(): void
    {
        $this->service->handle('producer-1', ['equipment-2'], 'user-1');
        $this->producers->withOpenEquipment(0);

        $this->expectException(EquipmentNotAvailable::class);

        $this->service->handle('producer-1', ['equipment-2'], 'user-1');
    }

    /** NFR-2 - somebody took the unit between the read and the write. */
    public function test_it_loses_the_race_for_the_last_unit(): void
    {
        $this->equipment->seed($this->anEquipment('equipment-3', 'Rode NTG5', 'RODE-NTG5', 1));
        $this->equipment->simulateConcurrentWithdrawalOn('equipment-3');

        try {
            $this->service->handle('producer-1', ['equipment-3'], 'user-1');
            $this->fail('Expected EquipmentNotAvailable.');
        } catch (EquipmentNotAvailable) {
            // The winner took the only unit; this caller neither added nor
            // removed anything.
            $this->assertSame(0, $this->equipment->stored('equipment-3')->availableUnits);
        }
    }

    public function test_a_producer_cannot_hold_more_than_two(): void
    {
        $this->producers->withOpenEquipment(1);

        $this->expectException(ProducerLimitExceeded::class);

        $this->service->handle('producer-1', ['equipment-1', 'equipment-2'], 'user-1');
    }

    public function test_a_producer_with_overdue_equipment_cannot_rent(): void
    {
        $this->producers->withOverdue(true);

        $this->expectException(ProducerHasOverdue::class);

        $this->service->handle('producer-1', ['equipment-1'], 'user-1');
    }

    public function test_an_unknown_producer_is_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->service->handle('producer-404', ['equipment-1'], 'user-1');
    }

    private function anEquipment(string $id, string $name, string $modelCode, int $units): Equipment
    {
        return Equipment::register(
            $id,
            $name,
            'Marca de prueba',
            ModelCode::fromString($modelCode),
            self::CATEGORY_ID,
            Quantity::fromInt($units),
        );
    }
}
