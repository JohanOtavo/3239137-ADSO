<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Equipment\Equipment;
use App\Domain\Shared\ModelCode;

interface EquipmentRepository
{
    /** Active equipment only: a soft-deleted row is a 404 (AC-04.3). */
    public function findActive(string $id): ?Equipment;

    public function existsActiveModelCode(ModelCode $modelCode): bool;

    public function add(Equipment $equipment): void;

    public function update(Equipment $equipment): void;

    /**
     * Writes the unit decrement only when nobody moved the counter between the
     * read and the write: available_units doubles as the version token
     * (ADR-002, NFR-2). Returns false when another caller won the race.
     */
    public function applyWithdrawal(Equipment $equipment, int $expectedAvailableUnits): bool;

    /** Adds one unit back without passing the total; the CHECK is the last barrier (AC-07.3). */
    public function applyRestock(string $equipmentId): bool;

    /** Whether the equipment has lines still out (AC-03.5). */
    public function hasOpenRentals(string $equipmentId): bool;

    /** @return PageResult<Equipment> */
    public function search(?string $query, ?string $categoryId, Pagination $page): PageResult;
}
