<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\OverdueRow;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Rental\Rental;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;

interface RentalRepository
{
    public function find(string $id): ?Rental;

    /** Inserts the rental together with all of its lines. */
    public function add(Rental $rental): void;

    /**
     * Sets returned_at only while it is still null. Returns false when another
     * request returned the line first, which is what stops the unit being
     * given back twice (AC-07.2).
     */
    public function markItemReturned(string $rentalId, string $itemId, DateTimeImmutable $now): bool;

    /** @return PageResult<Rental> */
    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?RentalStatus $status,
        ?string $producerId,
        Pagination $page,
        DateTimeImmutable $now,
    ): PageResult;

    /** @return list<OverdueRow> */
    public function overdue(DateTimeImmutable $now): array;
}
