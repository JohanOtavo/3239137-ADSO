<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-06.3 - No units left to hand out. */
final class EquipmentNotAvailable extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'EQUIPMENT_NOT_AVAILABLE';
    }
}
