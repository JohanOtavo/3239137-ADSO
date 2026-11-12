<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-05.1 - That producer document number already exists. */
final class DocumentTaken extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'DOCUMENT_TAKEN';
    }
}
