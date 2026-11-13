<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\RentalRepository;
use App\Application\Shared\OverdueRow;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Rental\Rental;
use App\Domain\Rental\RentalItem;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\RentalPolicy;
use App\Domain\Shared\RentalStatus;
use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseRentalRepository implements RentalRepository
{
    public function __construct(
        private ConnectionInterface $connection,
        private RentalPolicy $policy,
    ) {}

    public function find(string $id): ?Rental
    {
        $row = $this->connection->table(Tables::RENTAL)->where('id', $id)->first();

        return $row === null ? null : $this->hydrate($row, $this->itemsOf([$id]));
    }

    public function add(Rental $rental): void
    {
        $this->connection->table(Tables::RENTAL)->insert([
            'id' => $rental->id,
            'producer_id' => $rental->producerId,
            'rented_by_user_id' => $rental->rentedByUserId,
            'rented_at' => Tables::instant($rental->rentedAt),
        ]);

        $rows = [];

        foreach ($rental->items as $item) {
            $rows[] = [
                'id' => $item->id,
                'rental_id' => $rental->id,
                'equipment_id' => $item->equipmentId,
                'equipment_name' => $item->equipmentName,
                'model_code' => $item->modelCode->value,
                'category_name' => $item->categoryName,
                'due_at' => Tables::instant($item->dueAt),
                'returned_at' => null,
            ];
        }

        $this->connection->table(Tables::RENTAL_ITEM)->insert($rows);
    }

    public function markItemReturned(string $rentalId, string $itemId, DateTimeImmutable $now): bool
    {
        // The whereNull is the guard: a second return finds no matching row and
        // reports false instead of overwriting the first return date (AC-07.2).
        $affected = $this->connection->table(Tables::RENTAL_ITEM)
            ->where('id', $itemId)
            ->where('rental_id', $rentalId)
            ->whereNull('returned_at')
            ->update(['returned_at' => Tables::instant($now)]);

        return $affected === 1;
    }

    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?RentalStatus $status,
        ?string $producerId,
        Pagination $page,
        DateTimeImmutable $now,
    ): PageResult {
        $base = $this->connection->table(Tables::RENTAL);

        if ($from !== null) {
            $base->where('rented_at', '>=', Tables::instant($from));
        }

        if ($to !== null) {
            $base->where('rented_at', '<=', Tables::instant($to));
        }

        if ($producerId !== null && $producerId !== '') {
            $base->where('producer_id', $producerId);
        }

        if ($status !== null) {
            $this->filterByStatus($base, $status, $now);
        }

        $total = (clone $base)->count();

        $rows = $base->orderByDesc('rented_at')
            ->offset($page->offset())
            ->limit($page->size)
            ->get()
            ->all();

        $ids = array_map(static fn (object $row): string => (string) $row->id, $rows);
        $items = $this->itemsOf($ids);

        return new PageResult(
            array_map(fn (object $row): Rental => $this->hydrate($row, $items), $rows),
            $page->page,
            $page->size,
            $total,
        );
    }

    public function overdue(DateTimeImmutable $now): array
    {
        $rows = $this->connection->table(Tables::RENTAL_ITEM)
            ->join(Tables::RENTAL, Tables::RENTAL.'.id', '=', Tables::RENTAL_ITEM.'.rental_id')
            ->join(Tables::PRODUCER, Tables::PRODUCER.'.id', '=', Tables::RENTAL.'.producer_id')
            ->whereNull(Tables::RENTAL_ITEM.'.returned_at')
            ->where(Tables::RENTAL_ITEM.'.due_at', '<', Tables::instant($now))
            ->orderBy(Tables::RENTAL_ITEM.'.due_at')
            ->select([
                Tables::RENTAL_ITEM.'.id as item_id',
                Tables::RENTAL_ITEM.'.rental_id',
                Tables::RENTAL_ITEM.'.equipment_name',
                Tables::RENTAL_ITEM.'.due_at',
                Tables::PRODUCER.'.full_name as producer_name',
            ])
            ->get();

        return array_map(static function (object $row) use ($now): OverdueRow {
            $dueAt = Tables::requiredInstant((string) $row->due_at);

            return new OverdueRow(
                (string) $row->rental_id,
                (string) $row->item_id,
                (string) $row->equipment_name,
                (string) $row->producer_name,
                $dueAt,
                (int) $dueAt->diff($now)->days,
            );
        }, $rows->all());
    }

    /**
     * The state is not a column, so the filter is built from subqueries over the
     * lines of each rental (article VII, AC-08.3).
     */
    private function filterByStatus(Builder $base, RentalStatus $status, DateTimeImmutable $now): void
    {
        $pending = static fn (Builder $query): Builder => $query->from(Tables::RENTAL_ITEM)
            ->whereColumn(Tables::RENTAL_ITEM.'.rental_id', Tables::RENTAL.'.id')
            ->whereNull(Tables::RENTAL_ITEM.'.returned_at');

        $late = static fn (Builder $query): Builder => $pending($query)
            ->where(Tables::RENTAL_ITEM.'.due_at', '<', Tables::instant($now));

        match ($status) {
            RentalStatus::Closed => $base->whereNotExists($pending),
            RentalStatus::Overdue => $base->whereExists($late),
            RentalStatus::Open => $base->whereExists($pending)->whereNotExists($late),
        };
    }

    /**
     * Fetches the lines of several rentals in one query, so listing a page does
     * not fire one extra query per row.
     *
     * @param  list<string>  $rentalIds
     * @return array<string, list<RentalItem>>
     */
    private function itemsOf(array $rentalIds): array
    {
        if ($rentalIds === []) {
            return [];
        }

        $rows = $this->connection->table(Tables::RENTAL_ITEM)
            ->whereIn('rental_id', $rentalIds)
            ->orderBy('equipment_name')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(string) $row->rental_id][] = RentalItem::restore(
                (string) $row->id,
                (string) $row->equipment_id,
                (string) $row->equipment_name,
                ModelCode::fromString((string) $row->model_code),
                (string) $row->category_name,
                Tables::requiredInstant((string) $row->due_at),
                $row->returned_at === null ? null : Tables::toInstant((string) $row->returned_at),
            );
        }

        return $grouped;
    }

    /** @param array<string, list<RentalItem>> $items */
    private function hydrate(object $row, array $items): Rental
    {
        $id = (string) $row->id;

        return Rental::restore(
            $id,
            (string) $row->producer_id,
            (string) $row->rented_by_user_id,
            Tables::requiredInstant((string) $row->rented_at),
            $items[$id] ?? [],
            $this->policy,
        );
    }
}
