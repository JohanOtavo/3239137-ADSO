<?php

declare(strict_types=1);

namespace App\Application\Port;

/** The plain password dies here: neither the domain nor the database sees it (article IX, NFR-3). */
interface PasswordHasher
{
    public function hash(string $plainPassword): string;

    public function matches(string $plainPassword, string $hash): bool;
}
