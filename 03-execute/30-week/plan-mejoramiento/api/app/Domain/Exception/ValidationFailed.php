<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** A value does not satisfy its own shape rule. */
final class ValidationFailed extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'VALIDATION_ERROR';
    }
}
