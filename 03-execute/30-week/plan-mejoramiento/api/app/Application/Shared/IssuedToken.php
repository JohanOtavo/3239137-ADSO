<?php

declare(strict_types=1);

namespace App\Application\Shared;

/** What signing in returns (AC-01.1). */
final readonly class IssuedToken
{
    public function __construct(
        public string $accessToken,
        public int $expiresInSeconds,
    ) {}
}
