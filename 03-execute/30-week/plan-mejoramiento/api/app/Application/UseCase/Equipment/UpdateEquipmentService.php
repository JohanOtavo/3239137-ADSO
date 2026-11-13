<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\CategoryRepository;
use App\Application\Port\EquipmentRepository;
use App\Domain\Equipment\Equipment;
use App\Domain\Exception\ResourceNotFound;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Quantity;

/** US-03 - Replace name, brand, category and total units (AC-03.3). */
final readonly class UpdateEquipmentService
{
    public function __construct(
        private EquipmentRepository $equipment,
        private CategoryRepository $categories,
    ) {}

    public function handle(
        string $equipmentId,
        string $name,
        string $brand,
        string $categoryId,
        int $totalUnits,
    ): Equipment {
        $equipment = $this->equipment->findActive($equipmentId);

        if ($equipment === null) {
            throw new ResourceNotFound('Ese equipo no existe o está dado de baja.');
        }

        if ($this->categories->find($categoryId) === null) {
            throw new ValidationFailed('Esa categoría no existe.', [
                ['field' => 'category_id', 'issue' => 'categoría desconocida'],
            ]);
        }

        $updated = $equipment->replaceDetails($name, $brand, $categoryId, Quantity::fromInt($totalUnits));
        $this->equipment->update($updated);

        return $updated;
    }
}
