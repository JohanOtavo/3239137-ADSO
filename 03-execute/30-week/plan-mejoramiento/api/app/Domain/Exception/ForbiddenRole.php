<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-01.3 - The role is not allowed to perform this operation. */
final class ForbiddenRole extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'FORBIDDEN_ROLE';
    }
}
