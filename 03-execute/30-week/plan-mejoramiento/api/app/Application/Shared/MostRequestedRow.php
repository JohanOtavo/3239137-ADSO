<?php

declare(strict_types=1);

namespace App\Application\Shared;

/** Frozen equipment and category plus the count (AC-10.1). No producers, no advisors (AC-10.3). */
final readonly class MostRequestedRow
{
    public function __construct(
        public string $equipmentId,
        public string $equipmentName,
        public string $categoryName,
        public int $timesRented,
    ) {}
}
