<?php

declare(strict_types=1);

namespace App\Application\UseCase\Equipment;

use App\Application\Port\EquipmentRepository;
use App\Application\Shared\PageResult;
use App\Application\Shared\Pagination;
use App\Domain\Equipment\Equipment;

/** US-04 - Partial text over name or brand, category filter, active only (AC-04.1). */
final readonly class SearchEquipmentService
{
    public function __construct(private EquipmentRepository $equipment) {}

    /** @return PageResult<Equipment> */
    public function handle(?string $query, ?string $categoryId, Pagination $page): PageResult
    {
        return $this->equipment->search($query, $categoryId, $page);
    }
}
