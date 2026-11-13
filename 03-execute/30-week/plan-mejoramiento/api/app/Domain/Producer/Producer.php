<?php

declare(strict_types=1);

namespace App\Domain\Producer;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\DocumentNumber;

/**
 * The party that rents equipment.
 *
 * A producer does not sign in: it is a record the advisor keeps, not a user of
 * the system (spec section 1). Producers are never deleted (AC-05.3), so this
 * entity has no soft delete and no removal path at all.
 */
final readonly class Producer
{
    private function __construct(
        public string $id,
        public string $fullName,
        public DocumentNumber $documentNumber,
    ) {}

    public static function register(string $id, string $fullName, DocumentNumber $documentNumber): self
    {
        return new self($id, self::cleanName($fullName), $documentNumber);
    }

    public static function restore(string $id, string $fullName, DocumentNumber $documentNumber): self
    {
        return new self($id, $fullName, $documentNumber);
    }

    private static function cleanName(string $raw): string
    {
        $name = trim($raw);

        if ($name === '' || mb_strlen($name) > 200) {
            throw new ValidationFailed('El nombre del productor admite entre 1 y 200 caracteres.', [
                ['field' => 'full_name', 'issue' => 'entre 1 y 200 caracteres'],
            ]);
        }

        return $name;
    }
}
