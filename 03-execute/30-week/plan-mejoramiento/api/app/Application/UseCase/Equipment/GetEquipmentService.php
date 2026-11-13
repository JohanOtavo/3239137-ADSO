<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\EquipmentRepository;
use App\Domain\Equipment\Equipment;
use App\Domain\Exception\ResourceNotFound;

/** US-04 - Unknown or soft-deleted equipment is a 404 (AC-04.3). */
final readonly class GetEquipmentService
{
    public function __construct(private EquipmentRepository $equipment) {}

    public function handle(string $equipmentId): Equipment
    {
        $equipment = $this->equipment->findActive($equipmentId);

        if ($equipment === null) {
            throw new ResourceNotFound('Ese equipo no existe o está dado de baja.');
        }

        return $equipment;
    }
}
