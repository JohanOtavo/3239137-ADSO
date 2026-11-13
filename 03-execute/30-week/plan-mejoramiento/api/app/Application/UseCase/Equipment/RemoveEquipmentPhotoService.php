<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\EquipmentRepository;
use App\Application\Port\PhotoStorage;
use App\Domain\Exception\ResourceNotFound;

/**
 * Removes the photo.
 *
 * Mandatory order from data-model 7: null the key and commit first, delete the
 * binary second. The other way round leaves a row pointing at a file that is
 * already gone.
 */
final readonly class RemoveEquipmentPhotoService
{
    public function __construct(
        private EquipmentRepository $equipment,
        private PhotoStorage $photos,
    ) {}

    public function handle(string $equipmentId): void
    {
        $equipment = $this->equipment->findActive($equipmentId);

        if ($equipment === null) {
            throw new ResourceNotFound('Ese equipo no existe o está dado de baja.');
        }

        $previousKey = $equipment->photoKey;

        if ($previousKey === null) {
            return;
        }

        $this->equipment->update($equipment->removePhoto());
        $this->photos->delete($previousKey);
    }
}
