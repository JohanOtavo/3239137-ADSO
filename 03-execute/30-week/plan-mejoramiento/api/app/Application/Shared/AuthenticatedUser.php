<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Shared\Role;

/**
 * Who is operating.
 *
 * It comes from the token, never from the request body. A use case that took a
 * user id as a parameter would let a caller act as somebody else.
 */
final readonly class AuthenticatedUser
{
    public function __construct(
        public string $id,
        public Role $role,
    ) {}
}
