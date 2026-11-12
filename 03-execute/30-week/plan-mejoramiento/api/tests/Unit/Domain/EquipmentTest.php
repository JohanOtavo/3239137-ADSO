<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Equipment\Equipment;
use App\Domain\Exception\EquipmentNotAvailable;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** T-04 - Units never drop below zero and never exceed the total. */
final class EquipmentTest extends TestCase
{
    public function test_available_units_start_equal_to_the_total(): void
    {
        $equipment = $this->equipment(4);

        $this->assertSame(4, $equipment->totalUnits);
        $this->assertSame(4, $equipment->availableUnits);
        $this->assertNull($equipment->photoKey);
        $this->assertTrue($equipment->isActive());
    }

    public function test_withdrawing_takes_one_unit_and_leaves_the_original_untouched(): void
    {
        $equipment = $this->equipment(2);

        $afterRental = $equipment->withdraw();

        $this->assertSame(1, $afterRental->availableUnits);
        // The original was not mutated: the operation returned a new instance.
        $this->assertSame(2, $equipment->availableUnits);
    }

    public function test_cannot_withdraw_when_no_units_are_left(): void
    {
        $equipment = $this->equipment(1)->withdraw();

        $this->expectException(EquipmentNotAvailable::class);

        $equipment->withdraw();
    }

    public function test_restocking_cannot_push_available_above_the_total(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->equipment(1)->restock();
    }

    public function test_returning_gives_the_unit_back(): void
    {
        $equipment = $this->equipment(1)->withdraw()->restock();

        $this->assertSame(1, $equipment->availableUnits);
    }

    public function test_raising_the_total_raises_available_by_the_same_difference(): void
    {
        $equipment = $this->equipment(2)->withdraw();

        $updated = $equipment->replaceDetails('Sony FX6', 'Sony', 'category-1', Quantity::fromInt(5));

        $this->assertSame(5, $updated->totalUnits);
        $this->assertSame(4, $updated->availableUnits);
    }

    public function test_lowering_the_total_below_what_is_out_fails(): void
    {
        $equipment = $this->equipment(3)->withdraw()->withdraw()->withdraw();

        $this->expectException(ValidationFailed::class);

        $equipment->replaceDetails('Sony FX3', 'Sony', 'category-1', Quantity::fromInt(1));
    }

    public function test_the_model_code_is_not_editable(): void
    {
        $equipment = $this->equipment(2);

        $updated = $equipment->replaceDetails('Another name', 'Another brand', 'category-2', Quantity::fromInt(2));

        $this->assertSame('FX3-BODY', $updated->modelCode->value);
    }

    public function test_soft_delete_only_sets_the_date(): void
    {
        $equipment = $this->equipment(1)->softDelete(new DateTimeImmutable('2026-11-13T10:00:00Z'));

        $this->assertFalse($equipment->isActive());
        $this->assertSame(1, $equipment->totalUnits);
    }

    public function test_a_photo_key_cannot_be_blank(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->equipment(1)->attachPhoto('   ');
    }

    public function test_removing_the_photo_leaves_the_key_null(): void
    {
        $equipment = $this->equipment(1)->attachPhoto('abc.png')->removePhoto();

        $this->assertNull($equipment->photoKey);
    }

    private function equipment(int $totalUnits): Equipment
    {
        return Equipment::register(
            'equipment-1',
            'Sony FX3',
            'Sony',
            ModelCode::fromString('FX3-BODY'),
            'category-1',
            Quantity::fromInt($totalUnits),
        );
    }
}
