<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\Clock;
use App\Application\Port\EquipmentRepository;
use App\Domain\Exception\EquipmentHasOpenRentals;
use App\Domain\Exception\ResourceNotFound;

/** US-03 - Soft delete; equipment with open rentals cannot go (AC-03.5). */
final readonly class SoftDeleteEquipmentService
{
    public function __construct(
        private EquipmentRepository $equipment,
        private Clock $clock,
    ) {}

    public function handle(string $equipmentId): void
    {
        $equipment = $this->equipment->findActive($equipmentId);

        if ($equipment === null) {
            throw new ResourceNotFound('Ese equipo no existe o está dado de baja.');
        }

        // Checked before deleting rather than relying on the foreign key: the
        // FK would reject the delete with a database error, and the contract
        // promises a 409 with a reason.
        if ($this->equipment->hasOpenRentals($equipmentId)) {
            throw new EquipmentHasOpenRentals('Ese equipo tiene alquileres sin devolver: no se puede dar de baja.');
        }

        $this->equipment->update($equipment->softDelete($this->clock->now()));
    }
}
