<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationFailed;

/** A unit count: an integer above zero (AC-03.3). */
final readonly class Quantity
{
    private function __construct(public int $value) {}

    public static function fromInt(int $value, string $field = 'total_units'): self
    {
        if ($value < 1) {
            throw new ValidationFailed('La cantidad de unidades debe ser al menos 1.', [
                ['field' => $field, 'issue' => 'debe ser mayor o igual a 1'],
            ]);
        }

        return new self($value);
    }
}
