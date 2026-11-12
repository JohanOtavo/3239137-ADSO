<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\InvalidDateRange;
use App\Domain\Shared\DateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** T-03 - A range with both ends included (AC-10.2). */
final class DateRangeTest extends TestCase
{
    public function test_accepts_a_single_day_range(): void
    {
        $day = new DateTimeImmutable('2026-11-13T00:00:00Z');

        $range = DateRange::between($day, $day);

        $this->assertSame($day, $range->from);
        $this->assertSame($day, $range->to);
    }

    public function test_accepts_a_range_in_order(): void
    {
        $range = DateRange::between(
            new DateTimeImmutable('2026-11-01T00:00:00Z'),
            new DateTimeImmutable('2026-11-30T00:00:00Z'),
        );

        $this->assertTrue($range->from < $range->to);
    }

    public function test_rejects_a_range_whose_end_is_before_its_start(): void
    {
        $this->expectException(InvalidDateRange::class);

        DateRange::between(
            new DateTimeImmutable('2026-11-30T00:00:00Z'),
            new DateTimeImmutable('2026-11-01T00:00:00Z'),
        );
    }
}
