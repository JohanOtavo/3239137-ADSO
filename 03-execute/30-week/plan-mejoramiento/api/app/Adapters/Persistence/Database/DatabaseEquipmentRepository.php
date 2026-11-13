<?php

declare(strict_types=1);

namespace App\Adapters\Persistence\Database;

use App\Application\Port\EquipmentRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Equipment\Equipment;
use App\Domain\Shared\ModelCode;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

final readonly class DatabaseEquipmentRepository implements EquipmentRepository
{
    public function __construct(private ConnectionInterface $connection) {}

    public function findActive(string $id): ?Equipment
    {
        $row = $this->activeEquipment()->where('id', $id)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function existsActiveModelCode(ModelCode $modelCode): bool
    {
        return $this->activeEquipment()->where('model_code', $modelCode->value)->exists();
    }

    public function add(Equipment $equipment): void
    {
        $this->connection->table(Tables::EQUIPMENT)->insert($this->columnsOf($equipment));
    }

    public function update(Equipment $equipment): void
    {
        $this->connection->table(Tables::EQUIPMENT)
            ->where('id', $equipment->id)
            ->update($this->columnsOf($equipment));
    }

    /**
     * Conditional write: it only lands when available_units is still what we
     * read. Those units double as the version token, so two advisors racing
     * for the last one produce a winner and a 409 (ADR-002, NFR-2).
     */
    public function applyWithdrawal(Equipment $equipment, int $expectedAvailableUnits): bool
    {
        $affected = $this->connection->table(Tables::EQUIPMENT)
            ->where('id', $equipment->id)
            ->where('available_units', $expectedAvailableUnits)
            ->whereNull('deleted_at')
            ->update(['available_units' => $equipment->availableUnits]);

        return $affected === 1;
    }

    /** Adds one unit without passing the total; the CHECK is the last barrier (AC-07.3). */
    public function applyRestock(string $equipmentId): bool
    {
        $affected = $this->connection->table(Tables::EQUIPMENT)
            ->where('id', $equipmentId)
            ->whereColumn('available_units', '<', 'total_units')
            ->update(['available_units' => $this->connection->raw('available_units + 1')]);

        return $affected === 1;
    }

    public function hasOpenRentals(string $equipmentId): bool
    {
        return $this->connection->table(Tables::RENTAL_ITEM)
            ->where('equipment_id', $equipmentId)
            ->whereNull('returned_at')
            ->exists();
    }

    public function search(?string $query, ?string $categoryId, Pagination $page): PageResult
    {
        $base = $this->activeEquipment();

        if ($query !== null && trim($query) !== '') {
            $term = '%'.trim($query).'%';
            $base->where(function (Builder $where) use ($term): void {
                $where->where('name', 'ilike', $term)->orWhere('brand', 'ilike', $term);
            });
        }

        if ($categoryId !== null && $categoryId !== '') {
            $base->where('category_id', $categoryId);
        }

        $total = (clone $base)->count();

        $rows = $base->orderBy('name')
            ->offset($page->offset())
            ->limit($page->size)
            ->get();

        return new PageResult(
            array_map(fn (object $row): Equipment => $this->hydrate($row), $rows->all()),
            $page->page,
            $page->size,
            $total,
        );
    }

    /** Soft-deleted equipment does not exist for the catalogue (AC-03.5, AC-04.1). */
    private function activeEquipment(): Builder
    {
        return $this->connection->table(Tables::EQUIPMENT)->whereNull('deleted_at');
    }

    /** @return array<string, mixed> */
    private function columnsOf(Equipment $equipment): array
    {
        return [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'brand' => $equipment->brand,
            'model_code' => $equipment->modelCode->value,
            'category_id' => $equipment->categoryId,
            'total_units' => $equipment->totalUnits,
            'available_units' => $equipment->availableUnits,
            'photo_key' => $equipment->photoKey,
            'deleted_at' => $equipment->deletedAt === null ? null : Tables::instant($equipment->deletedAt),
        ];
    }

    private function hydrate(object $row): Equipment
    {
        return Equipment::restore(
            (string) $row->id,
            (string) $row->name,
            (string) $row->brand,
            ModelCode::fromString((string) $row->model_code),
            (string) $row->category_id,
            (int) $row->total_units,
            (int) $row->available_units,
            $row->photo_key === null ? null : (string) $row->photo_key,
            Tables::toInstant($row->deleted_at === null ? null : (string) $row->deleted_at),
        );
    }
}
