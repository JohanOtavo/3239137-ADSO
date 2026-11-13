<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\TokenIssuer;
use App\Application\Shared\AuthenticatedUser;
use App\Application\Shared\IssuedToken;
use App\Domain\Shared\Role;

/** Issues a readable token so a test can assert who it was issued for. */
final class PredictableTokenIssuer implements TokenIssuer
{
    public function issue(string $userId, Role $role): IssuedToken
    {
        return new IssuedToken(sprintf('token-for-%s-as-%s', $userId, $role->value), 28800);
    }

    public function verify(string $token): ?AuthenticatedUser
    {
        if (preg_match('/^token-for-(.+)-as-(admin|advisor)$/', $token, $parts) !== 1) {
            return null;
        }

        return new AuthenticatedUser($parts[1], Role::from($parts[2]));
    }
}
