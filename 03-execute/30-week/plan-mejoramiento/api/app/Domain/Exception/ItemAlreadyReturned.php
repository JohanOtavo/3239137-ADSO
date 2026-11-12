<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-07.2 - That line was already returned. */
final class ItemAlreadyReturned extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'ITEM_ALREADY_RETURNED';
    }
}
