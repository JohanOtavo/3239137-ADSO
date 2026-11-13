<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Rental\ReturnRentalItemService;
use App\Domain\Equipment\Equipment;
use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Rental\Rental;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\Quantity;
use App\Domain\Shared\RentalPolicy;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\FixedClock;
use Tests\Support\Fake\InMemoryEquipment;
use Tests\Support\Fake\InMemoryRentals;
use Tests\Support\Fake\InstantTransactions;

/** T-10 - US-07: returning gives one unit back, and only once. */
final class ReturnRentalItemServiceTest extends TestCase
{
    private InMemoryEquipment $equipment;

    private InMemoryRentals $rentals;

    private ReturnRentalItemService $service;

    protected function setUp(): void
    {
        $this->equipment = new InMemoryEquipment();
        $this->rentals = new InMemoryRentals();

        $equipment = Equipment::register(
            'equipment-1',
            'Sony FX3',
            'Sony',
            ModelCode::fromString('FX3-BODY'),
            'category-1',
            Quantity::fromInt(2),
        );

        // One unit is already out: that is the state a return starts from.
        $this->equipment->seed($equipment->withdraw());

        $this->rentals->add(
            Rental::open('rental-1', 'producer-1', 'user-1', new DateTimeImmutable('2026-11-13T14:15:00Z'), new RentalPolicy())
                ->addItem('item-1', 'equipment-1', 'Sony FX3', ModelCode::fromString('FX3-BODY'), 'Cámaras'),
        );

        $this->service = new ReturnRentalItemService(
            $this->rentals,
            $this->equipment,
            new InstantTransactions(),
            new FixedClock(new DateTimeImmutable('2026-11-15T09:00:00Z')),
        );
    }

    public function test_returning_sets_the_date_and_gives_the_unit_back(): void
    {
        $rental = $this->service->handle('rental-1', 'item-1');

        $this->assertNotNull($rental->item('item-1')->returnedAt);
        $this->assertSame(2, $this->equipment->stored('equipment-1')->availableUnits);
    }

    public function test_returning_everything_closes_the_rental(): void
    {
        $rental = $this->service->handle('rental-1', 'item-1');

        $this->assertSame(RentalStatus::Closed, $rental->statusAt(new DateTimeImmutable('2026-11-16T09:00:00Z')));
    }

    /** AC-07.2 - the unit must not come back twice. */
    public function test_returning_twice_does_not_give_the_unit_back_twice(): void
    {
        $this->service->handle('rental-1', 'item-1');

        try {
            $this->service->handle('rental-1', 'item-1');
            $this->fail('Expected ItemAlreadyReturned.');
        } catch (ItemAlreadyReturned) {
            $this->assertSame(2, $this->equipment->stored('equipment-1')->availableUnits);
        }
    }

    public function test_a_line_from_another_rental_is_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->service->handle('rental-1', 'item-404');
    }

    public function test_an_unknown_rental_is_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->service->handle('rental-404', 'item-1');
    }
}
