<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Role;

/**
 * An internal operator.
 *
 * The domain only ever sees the hash. The plain password never reaches this
 * class, which is what makes article IX checkable rather than aspirational:
 * there is no field here that could leak one.
 */
final readonly class User
{
    private function __construct(
        public string $id,
        public string $username,
        public string $passwordHash,
        public Role $role,
    ) {}

    public static function register(string $id, string $username, string $passwordHash, Role $role): self
    {
        if ($passwordHash === '') {
            throw new ValidationFailed('El usuario necesita una clave.', [
                ['field' => 'password', 'issue' => 'obligatoria'],
            ]);
        }

        return new self($id, self::normalizeUsername($username), $passwordHash, $role);
    }

    public static function restore(string $id, string $username, string $passwordHash, Role $role): self
    {
        return new self($id, $username, $passwordHash, $role);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Stored lowercased and trimmed (AC-02.2).
     *
     * Normalising here rather than at the HTTP edge means the uniqueness index
     * and the login lookup agree on one shape, so "Ana " and "ana" cannot both
     * exist.
     */
    public static function normalizeUsername(string $raw): string
    {
        $username = mb_strtolower(trim($raw));

        if ($username === '' || mb_strlen($username) > 120) {
            throw new ValidationFailed('El nombre de usuario admite entre 1 y 120 caracteres.', [
                ['field' => 'username', 'issue' => 'entre 1 y 120 caracteres'],
            ]);
        }

        return $username;
    }
}
