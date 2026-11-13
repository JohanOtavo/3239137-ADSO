<?php

declare(strict_types=1);

namespace App\Adapters\Security;

use App\Application\Port\PasswordHasher;

/**
 * Argon2id when the container's PHP was built with it, bcrypt otherwise (D-09).
 *
 * What never changes either way: only the hash is stored, and the plain
 * password does not leave this file (article IX).
 */
final class NativePasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, $this->algorithm());
    }

    public function matches(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }

    private function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }
}
