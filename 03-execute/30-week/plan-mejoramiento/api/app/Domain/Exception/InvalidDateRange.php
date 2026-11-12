<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-10.2 - The end of the range is before its start. */
final class InvalidDateRange extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'INVALID_DATE_RANGE';
    }
}
