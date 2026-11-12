<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-06.2 - The same equipment appears twice in one rental. */
final class DuplicateEquipmentInRental extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'DUPLICATE_EQUIPMENT_IN_RENTAL';
    }
}
