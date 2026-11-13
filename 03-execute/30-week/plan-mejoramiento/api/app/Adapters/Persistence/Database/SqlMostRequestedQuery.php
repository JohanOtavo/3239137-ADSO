<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\MostRequestedQuery;
use App\Application\Shared\MostRequestedRow;
use App\Domain\Shared\DateRange;
use Illuminate\Database\ConnectionInterface;

/**
 * One aggregated query instead of loading rentals and counting in PHP
 * (ADR-004).
 *
 * The grouping keys are the frozen columns on the line, never a join against
 * the catalogue: a report for a closed period keeps reading the name the
 * equipment had when it was rented, even after it is renamed or retired.
 */
final readonly class SqlMostRequestedQuery implements MostRequestedQuery
{
    public function __construct(private ConnectionInterface $connection) {}

    public function rows(DateRange $range): array
    {
        $rows = $this->connection->table(Tables::RENTAL_ITEM)
            ->join(Tables::RENTAL, Tables::RENTAL.'.id', '=', Tables::RENTAL_ITEM.'.rental_id')
            ->whereBetween(Tables::RENTAL.'.rented_at', [
                Tables::instant($range->from),
                Tables::instant($range->to),
            ])
            ->groupBy(
                Tables::RENTAL_ITEM.'.equipment_id',
                Tables::RENTAL_ITEM.'.equipment_name',
                Tables::RENTAL_ITEM.'.category_name',
            )
            ->orderByDesc('times_rented')
            ->orderBy(Tables::RENTAL_ITEM.'.equipment_name')
            ->select([
                Tables::RENTAL_ITEM.'.equipment_id',
                Tables::RENTAL_ITEM.'.equipment_name',
                Tables::RENTAL_ITEM.'.category_name',
                $this->connection->raw('count(*) as times_rented'),
            ])
            ->get();

        return array_map(static fn (object $row): MostRequestedRow => new MostRequestedRow(
            (string) $row->equipment_id,
            (string) $row->equipment_name,
            (string) $row->category_name,
            (int) $row->times_rented,
        ), $rows->all());
    }
}
