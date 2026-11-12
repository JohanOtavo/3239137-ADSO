<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-03.2 - That model code already exists among active equipment. */
final class ModelCodeTaken extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'MODEL_CODE_TAKEN';
    }
}
