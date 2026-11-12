<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-02.2 - That username already exists. */
final class UsernameTaken extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'USERNAME_TAKEN';
    }
}
