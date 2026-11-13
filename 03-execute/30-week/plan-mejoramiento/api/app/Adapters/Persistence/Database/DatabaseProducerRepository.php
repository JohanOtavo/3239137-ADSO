<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\ProducerRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Producer\Producer;
use App\Domain\Shared\DocumentNumber;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseProducerRepository implements ProducerRepository
{
    public function __construct(private ConnectionInterface $connection) {}

    public function find(string $id): ?Producer
    {
        $row = $this->connection->table(Tables::PRODUCER)->where('id', $id)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function existsByDocument(DocumentNumber $documentNumber): bool
    {
        return $this->connection->table(Tables::PRODUCER)
            ->where('document_number', $documentNumber->value)
            ->exists();
    }

    public function add(Producer $producer): void
    {
        $this->connection->table(Tables::PRODUCER)->insert([
            'id' => $producer->id,
            'full_name' => $producer->fullName,
            'document_number' => $producer->documentNumber->value,
        ]);
    }

    public function search(?string $query, Pagination $page): PageResult
    {
        $base = $this->connection->table(Tables::PRODUCER);

        if ($query !== null && trim($query) !== '') {
            $term = '%'.trim($query).'%';
            // Grouped so the OR cannot escape and widen an outer filter.
            $base->where(function (Builder $where) use ($term): void {
                $where->where('full_name', 'ilike', $term)
                    ->orWhere('document_number', 'ilike', $term);
            });
        }

        $total = (clone $base)->count();

        $rows = $base->orderBy('full_name')
            ->offset($page->offset())
            ->limit($page->size)
            ->get();

        return new PageResult(
            array_map(fn (object $row): Producer => $this->hydrate($row), $rows->all()),
            $page->page,
            $page->size,
            $total,
        );
    }

    public function countOpenEquipment(string $producerId): int
    {
        return $this->openItems($producerId)->count();
    }

    public function hasOverdue(string $producerId, DateTimeImmutable $now): bool
    {
        return $this->openItems($producerId)
            ->where(Tables::RENTAL_ITEM.'.due_at', '<', Tables::instant($now))
            ->exists();
    }

    /** Lines still out for this producer, across every one of its rentals. */
    private function openItems(string $producerId): Builder
    {
        return $this->connection->table(Tables::RENTAL_ITEM)
            ->join(Tables::RENTAL, Tables::RENTAL.'.id', '=', Tables::RENTAL_ITEM.'.rental_id')
            ->where(Tables::RENTAL.'.producer_id', $producerId)
            ->whereNull(Tables::RENTAL_ITEM.'.returned_at');
    }

    private function hydrate(object $row): Producer
    {
        return Producer::restore(
            (string) $row->id,
            (string) $row->full_name,
            DocumentNumber::fromString((string) $row->document_number),
        );
    }
}
