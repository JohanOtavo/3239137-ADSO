<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Quantity;
use PHPUnit\Framework\TestCase;

/** T-03 - A unit count is an integer above zero (AC-03.3). */
final class QuantityTest extends TestCase
{
    public function test_accepts_one_as_the_smallest_valid_count(): void
    {
        $this->assertSame(1, Quantity::fromInt(1)->value);
    }

    public function test_rejects_zero(): void
    {
        $this->expectException(ValidationFailed::class);

        Quantity::fromInt(0);
    }

    public function test_rejects_a_negative_count(): void
    {
        $this->expectException(ValidationFailed::class);

        Quantity::fromInt(-3);
    }

    public function test_names_the_field_it_was_given(): void
    {
        try {
            Quantity::fromInt(0, 'total_units');
            $this->fail('Expected ValidationFailed.');
        } catch (ValidationFailed $failure) {
            $this->assertSame('total_units', $failure->details()[0]['field']);
        }
    }
}
