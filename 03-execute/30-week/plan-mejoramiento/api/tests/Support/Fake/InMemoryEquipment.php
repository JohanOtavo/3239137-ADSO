<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\EquipmentRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Equipment\Equipment;
use App\Domain\Shared\ModelCode;

/**
 * An in-memory catalogue.
 *
 * applyWithdrawal mirrors the real adapter's conditional write: it only
 * succeeds when the available count is still what the caller read. That is
 * what lets the race for the last unit be reproduced without PostgreSQL.
 */
final class InMemoryEquipment implements EquipmentRepository
{
    /** @var array<string, Equipment> */
    private array $equipment = [];

    /** @var array<string, bool> */
    private array $openRentals = [];

    private ?string $raceOnRead = null;

    public function seed(Equipment $equipment): void
    {
        $this->equipment[$equipment->id] = $equipment;
    }

    public function stored(string $id): ?Equipment
    {
        return $this->equipment[$id] ?? null;
    }

    public function withOpenRentals(string $equipmentId): void
    {
        $this->openRentals[$equipmentId] = true;
    }

    /**
     * Makes another caller take a unit immediately after the next read of this
     * equipment, so the caller holds a stale value. That is exactly the race
     * NFR-2 describes.
     */
    public function simulateConcurrentWithdrawalOn(string $equipmentId): void
    {
        $this->raceOnRead = $equipmentId;
    }

    public function findActive(string $id): ?Equipment
    {
        $equipment = $this->equipment[$id] ?? null;

        if ($equipment === null || ! $equipment->isActive()) {
            return null;
        }

        if ($this->raceOnRead === $id) {
            $this->raceOnRead = null;
            $this->equipment[$id] = $equipment->withdraw();
        }

        return $equipment;
    }

    public function existsActiveModelCode(ModelCode $modelCode): bool
    {
        foreach ($this->equipment as $equipment) {
            if ($equipment->isActive() && $equipment->modelCode->equals($modelCode)) {
                return true;
            }
        }

        return false;
    }

    public function add(Equipment $equipment): void
    {
        $this->equipment[$equipment->id] = $equipment;
    }

    public function update(Equipment $equipment): void
    {
        $this->equipment[$equipment->id] = $equipment;
    }

    public function applyWithdrawal(Equipment $equipment, int $expectedAvailableUnits): bool
    {
        $current = $this->equipment[$equipment->id] ?? null;

        if ($current === null || $current->availableUnits !== $expectedAvailableUnits) {
            return false;
        }

        $this->equipment[$equipment->id] = $equipment;

        return true;
    }

    public function applyRestock(string $equipmentId): bool
    {
        $equipment = $this->equipment[$equipmentId] ?? null;

        if ($equipment === null || $equipment->availableUnits >= $equipment->totalUnits) {
            return false;
        }

        $this->equipment[$equipmentId] = $equipment->restock();

        return true;
    }

    public function hasOpenRentals(string $equipmentId): bool
    {
        return $this->openRentals[$equipmentId] ?? false;
    }

    public function search(?string $query, ?string $categoryId, Pagination $page): PageResult
    {
        $active = array_values(array_filter($this->equipment, fn (Equipment $e): bool => $e->isActive()));

        return new PageResult($active, $page->page, $page->size, count($active));
    }
}
