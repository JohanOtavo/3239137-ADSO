<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use App\Domain\Exception\ValidationFailed;

/** The only two roles in the system (spec section 1). */
enum Role: string
{
    case Admin = 'admin';
    case Advisor = 'advisor';

    public static function fromString(string $raw): self
    {
        $role = self::tryFrom(trim($raw));

        if ($role === null) {
            throw new ValidationFailed('El rol debe ser admin o advisor.', [
                ['field' => 'role', 'issue' => 'rol desconocido'],
            ]);
        }

        return $role;
    }

    /**
     * The admin role is provisioned by the deployment; nobody grants it at
     * runtime (AC-02.3). Asking for it through the API is rejected before the
     * request reaches the database.
     */
    public static function assignableByApi(string $raw): self
    {
        $role = self::fromString($raw);

        if ($role === self::Admin) {
            throw new ValidationFailed('El rol admin no se otorga por la API.', [
                ['field' => 'role', 'issue' => 'solo se admite advisor'],
            ]);
        }

        return $role;
    }
}
