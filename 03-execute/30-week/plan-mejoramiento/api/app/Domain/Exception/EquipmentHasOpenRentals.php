<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-03.5 - Equipment with open rentals cannot be soft deleted. */
final class EquipmentHasOpenRentals extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'EQUIPMENT_HAS_OPEN_RENTALS';
    }
}
