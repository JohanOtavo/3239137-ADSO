<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Shared\MostRequestedRow;
use App\Application\UseCase\Report\MostRequestedReportService;
use App\Domain\Exception\InvalidDateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\StubMostRequested;

/** T-11 - US-10: the report and its range. */
final class MostRequestedReportServiceTest extends TestCase
{
    private StubMostRequested $query;

    private MostRequestedReportService $service;

    protected function setUp(): void
    {
        $this->query = new StubMostRequested();
        $this->service = new MostRequestedReportService($this->query);
    }

    public function test_it_returns_the_rows_the_query_produced(): void
    {
        $this->query->willReturn([new MostRequestedRow('equipment-1', 'Sony FX3', 'Cámaras', 12)]);

        $rows = $this->service->handle(
            new DateTimeImmutable('2026-11-01T00:00:00Z'),
            new DateTimeImmutable('2026-11-30T00:00:00Z'),
        );

        $this->assertCount(1, $rows);
        $this->assertSame(12, $rows[0]->timesRented);
        $this->assertSame('Cámaras', $rows[0]->categoryName);
    }

    public function test_it_hands_the_query_the_range_it_was_given(): void
    {
        $this->service->handle(
            new DateTimeImmutable('2026-11-01T00:00:00Z'),
            new DateTimeImmutable('2026-11-30T00:00:00Z'),
        );

        $this->assertNotNull($this->query->askedFor);
        $this->assertSame('2026-11-01', $this->query->askedFor->from->format('Y-m-d'));
        $this->assertSame('2026-11-30', $this->query->askedFor->to->format('Y-m-d'));
    }

    /** AC-10.2 - an inverted range is a 422, not an empty list. */
    public function test_an_inverted_range_is_rejected(): void
    {
        $this->expectException(InvalidDateRange::class);

        $this->service->handle(
            new DateTimeImmutable('2026-11-30T00:00:00Z'),
            new DateTimeImmutable('2026-11-01T00:00:00Z'),
        );
    }

    public function test_an_inverted_range_never_reaches_the_query(): void
    {
        try {
            $this->service->handle(
                new DateTimeImmutable('2026-11-30T00:00:00Z'),
                new DateTimeImmutable('2026-11-01T00:00:00Z'),
            );
            $this->fail('Expected InvalidDateRange.');
        } catch (InvalidDateRange) {
            $this->assertNull($this->query->askedFor);
        }
    }

    /** AC-10.2 - a range with no rentals is an empty list, not an error. */
    public function test_a_range_with_no_rentals_returns_an_empty_list(): void
    {
        $rows = $this->service->handle(
            new DateTimeImmutable('1990-01-01T00:00:00Z'),
            new DateTimeImmutable('1990-01-31T00:00:00Z'),
        );

        $this->assertSame([], $rows);
    }
}
