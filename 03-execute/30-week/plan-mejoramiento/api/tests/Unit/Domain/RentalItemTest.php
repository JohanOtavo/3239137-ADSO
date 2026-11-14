<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ItemAlreadyReturned;
use App\Domain\Rental\RentalItem;
use App\Domain\Shared\ModelCode;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * T-05 and T-29 - The rental line on its own.
 *
 * Two behaviours live here and nowhere else: the catalogue values are frozen
 * at the instant the equipment went out (article VIII, BR-4), and a line is
 * returned once (AC-07.2). Everything the report and the overdue list say
 * rests on those two.
 */
final class RentalItemTest extends TestCase
{
    private const DUE = '2026-11-17T10:00:00Z';

    public function test_a_fresh_line_is_out_and_carries_no_return_date(): void
    {
        $item = $this->line();

        $this->assertFalse($item->isReturned());
        $this->assertNull($item->returnedAt);
    }

    public function test_the_line_keeps_the_values_it_was_given_and_not_a_reference(): void
    {
        $item = $this->line();

        // The line holds strings, not an equipment identifier to look up. A
        // rename of the catalogue entry cannot reach them (ADR-004).
        $this->assertSame('Sony FX3', $item->equipmentName);
        $this->assertSame('FX3-BODY', $item->modelCode->value);
        $this->assertSame('Cámaras', $item->categoryName);
    }

    public function test_returning_sets_the_date_and_leaves_the_original_untouched(): void
    {
        $item = $this->line();
        $moment = new DateTimeImmutable('2026-11-16T09:30:00Z');

        $returned = $item->markReturned($moment);

        $this->assertTrue($returned->isReturned());
        $this->assertEquals($moment, $returned->returnedAt);
        // The original is a separate object: the line is immutable, so nothing
        // that held it sees it change underneath.
        $this->assertFalse($item->isReturned());
    }

    public function test_returning_a_line_twice_is_refused(): void
    {
        $returned = $this->line()->markReturned(new DateTimeImmutable('2026-11-16T09:30:00Z'));

        $this->expectException(ItemAlreadyReturned::class);

        $returned->markReturned(new DateTimeImmutable('2026-11-16T11:00:00Z'));
    }

    public function test_a_line_is_not_overdue_before_its_due_date(): void
    {
        $item = $this->line();

        $this->assertFalse($item->isOverdueAt(new DateTimeImmutable('2026-11-17T09:59:59Z')));
        $this->assertSame(0, $item->daysLateAt(new DateTimeImmutable('2026-11-17T09:59:59Z')));
    }

    public function test_a_line_is_overdue_the_instant_the_due_date_passes(): void
    {
        $item = $this->line();

        $this->assertTrue($item->isOverdueAt(new DateTimeImmutable('2026-11-17T10:00:01Z')));
    }

    public function test_days_late_counts_whole_days_and_not_started_ones(): void
    {
        $item = $this->line();

        // Thirty hours late is one whole day, not two: the overdue list says
        // how long something has been out, and rounding up overstates it.
        $this->assertSame(1, $item->daysLateAt(new DateTimeImmutable('2026-11-18T16:00:00Z')));
        $this->assertSame(3, $item->daysLateAt(new DateTimeImmutable('2026-11-20T10:00:00Z')));
    }

    public function test_a_returned_line_is_never_overdue_however_late_it_came_back(): void
    {
        $returned = $this->line()->markReturned(new DateTimeImmutable('2026-11-25T10:00:00Z'));

        // It came back eight days past the date and it still does not belong
        // on the overdue list: that list is what is still out (AC-09.1).
        $this->assertFalse($returned->isOverdueAt(new DateTimeImmutable('2026-11-26T10:00:00Z')));
        $this->assertSame(0, $returned->daysLateAt(new DateTimeImmutable('2026-11-26T10:00:00Z')));
    }

    public function test_a_restored_line_remembers_whether_it_came_back(): void
    {
        $restored = RentalItem::restore(
            'item-1',
            'equipment-1',
            'Sony FX3',
            ModelCode::fromString('FX3-BODY'),
            'Cámaras',
            new DateTimeImmutable(self::DUE),
            new DateTimeImmutable('2026-11-16T09:30:00Z'),
        );

        // Reading a row back has to produce the same line that was written,
        // or the state would depend on which path loaded it.
        $this->assertTrue($restored->isReturned());
    }

    private function line(): RentalItem
    {
        return RentalItem::freeze(
            'item-1',
            'equipment-1',
            'Sony FX3',
            ModelCode::fromString('FX3-BODY'),
            'Cámaras',
            new DateTimeImmutable(self::DUE),
        );
    }
}
