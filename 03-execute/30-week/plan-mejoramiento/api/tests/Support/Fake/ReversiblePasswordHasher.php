<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\PasswordHasher;

/**
 * A deliberately fake hasher: it prefixes instead of hashing.
 *
 * Using the real Argon2id here would make every authentication test slow for
 * no gain, because what these tests check is the use case, not the algorithm.
 * The real hasher has its own test against the real thing.
 */
final class ReversiblePasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function matches(string $plainPassword, string $hash): bool
    {
        return $hash === 'hashed:'.$plainPassword;
    }
}
