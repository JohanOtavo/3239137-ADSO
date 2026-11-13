<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\RentalRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Rental\Rental;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;

/**
 * markItemReturned mirrors the adapter's conditional write: the first caller
 * wins and every later one gets false, which is what stops the unit being
 * handed back twice.
 */
final class InMemoryRentals implements RentalRepository
{
    /** @var array<string, Rental> */
    private array $rentals = [];

    /** @var array<string, bool> */
    private array $returnedItems = [];

    public function find(string $id): ?Rental
    {
        return $this->rentals[$id] ?? null;
    }

    public function add(Rental $rental): void
    {
        $this->rentals[$rental->id] = $rental;
    }

    public function markItemReturned(string $rentalId, string $itemId, DateTimeImmutable $now): bool
    {
        $key = $rentalId.'/'.$itemId;

        if (isset($this->returnedItems[$key])) {
            return false;
        }

        $this->returnedItems[$key] = true;
        $this->rentals[$rentalId] = $this->rentals[$rentalId]->returnItem($itemId, $now);

        return true;
    }

    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?RentalStatus $status,
        ?string $producerId,
        Pagination $page,
        DateTimeImmutable $now,
    ): PageResult {
        $rentals = array_values($this->rentals);

        return new PageResult($rentals, $page->page, $page->size, count($rentals));
    }

    public function overdue(DateTimeImmutable $now): array
    {
        return [];
    }
}
