<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\MostRequestedQuery;
use App\Application\Shared\MostRequestedRow;
use App\Domain\Shared\DateRange;

/**
 * Records the range it was asked for.
 *
 * The aggregation itself is SQL and belongs to an integration test; what the
 * use case owns is building a valid range, so that is what this stub observes.
 */
final class StubMostRequested implements MostRequestedQuery
{
    public ?DateRange $askedFor = null;

    /** @var list<MostRequestedRow> */
    private array $rows = [];

    /** @param list<MostRequestedRow> $rows */
    public function willReturn(array $rows): void
    {
        $this->rows = $rows;
    }

    public function rows(DateRange $range): array
    {
        $this->askedFor = $range;

        return $this->rows;
    }
}
