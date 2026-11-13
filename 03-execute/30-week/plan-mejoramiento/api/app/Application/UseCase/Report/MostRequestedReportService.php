<?php

declare(strict_types=1);

namespace App\Application\UseCase\Report;

use App\Application\Port\MostRequestedQuery;
use App\Application\Shared\MostRequestedRow;
use App\Domain\Shared\DateRange;
use DateTimeImmutable;

/**
 * US-10 - Most requested equipment.
 *
 * It reads the frozen values on the line, so a closed period does not change
 * when equipment or its category is renamed later (AC-10.4, ADR-004).
 */
final readonly class MostRequestedReportService
{
    public function __construct(private MostRequestedQuery $query) {}

    /** @return list<MostRequestedRow> */
    public function handle(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        // Building the range here is what turns an inverted request into a 422
        // with a reason, rather than an empty result that looks like no data.
        return $this->query->rows(DateRange::between($from, $to));
    }
}
