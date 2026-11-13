<?php

declare(strict_types=1);

namespace App\Application\UseCase\Rental;

use App\Application\Port\Clock;
use App\Application\Port\RentalRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Rental\Rental;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;

/**
 * US-08 - List by range, state and producer, newest first (AC-08.2).
 *
 * The clock is passed down to the repository because filtering by state means
 * comparing due dates against now: the state is not a column to filter on.
 */
final readonly class SearchRentalsService
{
    public function __construct(
        private RentalRepository $rentals,
        private Clock $clock,
    ) {}

    /** @return PageResult<Rental> */
    public function handle(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?RentalStatus $status,
        ?string $producerId,
        Pagination $page,
    ): PageResult {
        return $this->rentals->search($from, $to, $status, $producerId, $page, $this->clock->now());
    }
}
