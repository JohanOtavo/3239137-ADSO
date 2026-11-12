<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationFailed;

/**
 * The code that identifies an equipment model: FX3-BODY, DJI-MINI4PRO (AC-03.2).
 *
 * It lives inside its owner's row and has no table of its own, because it has
 * no identity apart from the equipment it names (D-07).
 */
final readonly class ModelCode
{
    private function __construct(public string $value) {}

    public static function fromString(string $raw): self
    {
        // Uppercased before validating, so "fx3-body" is accepted and stored
        // in the one shape the catalogue displays.
        $value = strtoupper(trim($raw));

        if (preg_match('/^[A-Z0-9-]{4,20}$/', $value) !== 1) {
            throw new ValidationFailed('El código de modelo debe tener entre 4 y 20 caracteres: letras, dígitos o guiones.', [
                ['field' => 'model_code', 'issue' => 'entre 4 y 20 caracteres [A-Z0-9-]'],
            ]);
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
