<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\AuthenticatedUser;
use App\Application\Shared\IssuedToken;
use App\Domain\Shared\Role;

/** Issues and verifies the session token (api-contract 4). */
interface TokenIssuer
{
    public function issue(string $userId, Role $role): IssuedToken;

    /** Null when the token is malformed, forged or expired. The caller cannot tell which. */
    public function verify(string $token): ?AuthenticatedUser;
}
