<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-01.2 - Does not reveal whether the username or the password was wrong. */
final class InvalidCredentials extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'INVALID_CREDENTIALS';
    }
}
