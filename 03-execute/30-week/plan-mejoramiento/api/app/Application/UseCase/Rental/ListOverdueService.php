<?php

declare(strict_types=1);

namespace App\Application\UseCase\Rental;

use App\Application\Port\Clock;
use App\Application\Port\RentalRepository;
use App\Application\Shared\OverdueRow;

/** US-09 - Lines still out and past due, most days late first (AC-09.1, AC-09.2). */
final readonly class ListOverdueService
{
    public function __construct(
        private RentalRepository $rentals,
        private Clock $clock,
    ) {}

    /** @return list<OverdueRow> */
    public function handle(): array
    {
        return $this->rentals->overdue($this->clock->now());
    }
}
