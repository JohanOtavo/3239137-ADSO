<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\EquipmentRepository;
use App\Application\Port\PhotoStorage;
use App\Domain\Exception\ResourceNotFound;

/** Returns the photo binary, or 404 (api-contract 3.4). */
final readonly class GetEquipmentPhotoService
{
    public function __construct(
        private EquipmentRepository $equipment,
        private PhotoStorage $photos,
    ) {}

    public function handle(string $equipmentId): string
    {
        $equipment = $this->equipment->findActive($equipmentId);

        if ($equipment === null || $equipment->photoKey === null) {
            throw new ResourceNotFound('Ese equipo no tiene foto.');
        }

        $contents = $this->photos->read($equipment->photoKey);

        // A key that points at nothing is a 404 and not a 500: the row is
        // consistent, the binary simply is not there any more.
        if ($contents === null) {
            throw new ResourceNotFound('Ese equipo no tiene foto.');
        }

        return $contents;
    }
}
