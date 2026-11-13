<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\MostRequestedRow;
use App\Domain\Shared\DateRange;

/**
 * A read port, not a repository: one aggregated query rather than a walk over
 * entities (ADR-004).
 *
 * It reads the frozen values on the line and never the live catalogue, which
 * is what keeps a closed period stable.
 */
interface MostRequestedQuery
{
    /** @return list<MostRequestedRow> */
    public function rows(DateRange $range): array;
}
