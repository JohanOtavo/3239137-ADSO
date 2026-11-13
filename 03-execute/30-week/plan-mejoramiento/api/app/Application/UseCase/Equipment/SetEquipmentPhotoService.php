<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\EquipmentRepository;
use App\Application\Port\PhotoStorage;
use App\Domain\Equipment\Equipment;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use finfo;

/**
 * US-03 - Upload the photo (AC-03.4).
 *
 * The type is read from the content, not from the extension (api-contract 4):
 * renaming an executable to .png does not get it past this.
 */
final readonly class SetEquipmentPhotoService
{
    /** @param list<string> $allowedMimeTypes */
    public function __construct(
        private EquipmentRepository $equipment,
        private PhotoStorage $photos,
        private int $maxBytes,
        private array $allowedMimeTypes,
    ) {}

    public function handle(string $equipmentId, string $contents): Equipment
    {
        $equipment = $this->equipment->findActive($equipmentId);

        if ($equipment === null) {
            throw new ResourceNotFound('Ese equipo no existe o está dado de baja.');
        }

        if ($contents === '' || strlen($contents) > $this->maxBytes) {
            throw new ValidationFailed(
                sprintf('La foto no puede pasar de %d KB.', intdiv($this->maxBytes, 1024)),
                [['field' => 'file', 'issue' => 'tamaño fuera de rango']],
            );
        }

        $mimeType = $this->detectMimeType($contents);

        if (! in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new ValidationFailed('La foto debe ser JPG o PNG.', [
                ['field' => 'file', 'issue' => 'tipo no admitido'],
            ]);
        }

        // The old binary is removed only after the new key is committed, so a
        // failure halfway leaves a row pointing at a file that still exists
        // rather than at nothing (data-model 7).
        $previousKey = $equipment->photoKey;
        $updated = $equipment->attachPhoto($this->photos->put($contents, $mimeType));
        $this->equipment->update($updated);

        if ($previousKey !== null) {
            $this->photos->delete($previousKey);
        }

        return $updated;
    }

    private function detectMimeType(string $contents): string
    {
        $info = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $info->buffer($contents);

        return $mimeType === false ? 'application/octet-stream' : $mimeType;
    }
}
