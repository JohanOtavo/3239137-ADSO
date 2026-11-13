<?php

declare(strict_types=1);

namespace App\Application\Shared;

use DateTimeImmutable;

/**
 * One overdue line of the operational list (AC-09.1).
 *
 * The producer name does appear here, because somebody has to call them. The
 * report in api-contract 3.7 aggregates by equipment and exposes nobody.
 */
final readonly class OverdueRow
{
    public function __construct(
        public string $rentalId,
        public string $itemId,
        public string $equipmentName,
        public string $producerName,
        public DateTimeImmutable $dueAt,
        public int $daysLate,
    ) {}
}
