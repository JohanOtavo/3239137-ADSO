<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\CategoryRepository;
use App\Application\Port\EquipmentRepository;
use App\Application\Port\IdGenerator;
use App\Domain\Equipment\Equipment;
use App\Domain\Exception\ModelCodeTaken;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\ModelCode;
use App\Domain\Shared\Quantity;

/** US-03 - Create equipment (AC-03.1, AC-03.2). */
final readonly class CreateEquipmentService
{
    public function __construct(
        private EquipmentRepository $equipment,
        private CategoryRepository $categories,
        private IdGenerator $ids,
    ) {}

    public function handle(
        string $name,
        string $brand,
        string $modelCode,
        string $categoryId,
        int $totalUnits,
    ): Equipment {
        $code = ModelCode::fromString($modelCode);

        if ($this->categories->find($categoryId) === null) {
            throw new ValidationFailed('Esa categoría no existe.', [
                ['field' => 'category_id', 'issue' => 'categoría desconocida'],
            ]);
        }

        // Uniqueness is checked among active equipment only, so a retired code
        // can be reused (ADR-003). The partial index is the real guarantee;
        // this check exists to answer 409 instead of a database error.
        if ($this->equipment->existsActiveModelCode($code)) {
            throw new ModelCodeTaken(sprintf('Ya hay un equipo activo con el código %s.', $code->value));
        }

        $equipment = Equipment::register(
            $this->ids->next(),
            $name,
            $brand,
            $code,
            $categoryId,
            Quantity::fromInt($totalUnits),
        );

        $this->equipment->add($equipment);

        return $equipment;
    }
}
