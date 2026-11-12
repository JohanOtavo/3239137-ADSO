<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationFailed;

/** A producer's document number: trimmed, non-empty, up to 30 characters. */
final readonly class DocumentNumber
{
    private function __construct(public string $value) {}

    public static function fromString(string $raw): self
    {
        $value = trim($raw);

        if ($value === '' || mb_strlen($value) > 30) {
            throw new ValidationFailed('El documento del productor admite entre 1 y 30 caracteres.', [
                ['field' => 'document_number', 'issue' => 'entre 1 y 30 caracteres'],
            ]);
        }

        return new self($value);
    }
}
