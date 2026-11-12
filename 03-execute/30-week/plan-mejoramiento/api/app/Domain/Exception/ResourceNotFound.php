<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** The resource does not exist, or is soft deleted. */
final class ResourceNotFound extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'NOT_FOUND';
    }
}
