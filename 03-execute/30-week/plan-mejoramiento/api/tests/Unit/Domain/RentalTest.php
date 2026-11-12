<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\DuplicateEquipmentInRental;
use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Rental\Rental;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\RentalPolicy;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * T-05 - Computed state, four day term and the line limit.
 *
 * The clock arrives as an argument, so a due date is checked without waiting
 * four days for it.
 */
final class RentalTest extends TestCase
{
    private const RENTED_AT = '2026-11-12T14:15:00Z';

    public function test_the_line_is_due_four_days_later(): void
    {
        $rental = $this->rentalWithOneItem();

        $this->assertSame('2026-11-16T14:15:00+00:00', $rental->items[0]->dueAt->format('c'));
    }

    public function test_the_line_freezes_name_model_code_and_category(): void
    {
        $item = $this->rentalWithOneItem()->items[0];

        $this->assertSame('Sony FX3', $item->equipmentName);
        $this->assertSame('FX3-BODY', $item->modelCode->value);
        $this->assertSame('Cámaras', $item->categoryName);
    }

    public function test_the_same_equipment_cannot_repeat(): void
    {
        $rental = $this->rentalWithOneItem();

        $this->expectException(DuplicateEquipmentInRental::class);

        $rental->addItem('item-2', 'equipment-1', 'Sony FX3', ModelCode::fromString('FX3-BODY'), 'Cámaras');
    }

    public function test_a_rental_takes_no_more_than_two_lines(): void
    {
        $rental = $this->rentalWithOneItem()
            ->addItem('item-2', 'equipment-2', 'DJI Mini 4 Pro', ModelCode::fromString('DJI-MINI4PRO'), 'Drones');

        $this->expectException(ValidationFailed::class);

        $rental->addItem('item-3', 'equipment-3', 'Rode NTG5', ModelCode::fromString('RODE-NTG5'), 'Audio');
    }

    public function test_a_rental_without_lines_is_not_recorded(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->emptyRental()->ensureReadyToRecord();
    }

    public function test_it_is_open_before_the_due_date(): void
    {
        $rental = $this->rentalWithOneItem();

        $this->assertSame(RentalStatus::Open, $rental->statusAt(new DateTimeImmutable('2026-11-14T00:00:00Z')));
    }

    public function test_it_is_overdue_after_the_due_date(): void
    {
        $rental = $this->rentalWithOneItem();

        $this->assertSame(RentalStatus::Overdue, $rental->statusAt(new DateTimeImmutable('2026-11-18T00:00:00Z')));
    }

    public function test_it_closes_once_everything_is_returned(): void
    {
        $rental = $this->rentalWithOneItem()->returnItem('item-1', new DateTimeImmutable('2026-11-14T09:00:00Z'));

        $this->assertSame(RentalStatus::Closed, $rental->statusAt(new DateTimeImmutable('2026-11-18T00:00:00Z')));
        $this->assertSame(0, $rental->openItemsCount());
    }

    public function test_a_returned_line_is_not_returned_twice(): void
    {
        $rental = $this->rentalWithOneItem()->returnItem('item-1', new DateTimeImmutable('2026-11-14T09:00:00Z'));

        $this->expectException(ItemAlreadyReturned::class);

        $rental->returnItem('item-1', new DateTimeImmutable('2026-11-15T09:00:00Z'));
    }

    public function test_returning_a_line_from_another_rental_is_not_found(): void
    {
        $this->expectException(ResourceNotFound::class);

        $this->rentalWithOneItem()->returnItem('item-404', new DateTimeImmutable('2026-11-14T09:00:00Z'));
    }

    public function test_it_counts_the_days_late(): void
    {
        $item = $this->rentalWithOneItem()->items[0];

        $this->assertSame(3, $item->daysLateAt(new DateTimeImmutable('2026-11-19T14:15:00Z')));
        $this->assertSame(0, $item->daysLateAt(new DateTimeImmutable('2026-11-14T14:15:00Z')));
    }

    public function test_one_line_out_of_two_still_leaves_the_rental_open(): void
    {
        $rental = $this->rentalWithOneItem()
            ->addItem('item-2', 'equipment-2', 'DJI Mini 4 Pro', ModelCode::fromString('DJI-MINI4PRO'), 'Drones')
            ->returnItem('item-1', new DateTimeImmutable('2026-11-14T09:00:00Z'));

        $this->assertSame(RentalStatus::Open, $rental->statusAt(new DateTimeImmutable('2026-11-14T10:00:00Z')));
        $this->assertSame(1, $rental->openItemsCount());
    }

    private function emptyRental(): Rental
    {
        return Rental::open('rental-1', 'producer-1', 'user-1', new DateTimeImmutable(self::RENTED_AT), new RentalPolicy());
    }

    private function rentalWithOneItem(): Rental
    {
        return $this->emptyRental()->addItem(
            'item-1',
            'equipment-1',
            'Sony FX3',
            ModelCode::fromString('FX3-BODY'),
            'Cámaras',
        );
    }
}
