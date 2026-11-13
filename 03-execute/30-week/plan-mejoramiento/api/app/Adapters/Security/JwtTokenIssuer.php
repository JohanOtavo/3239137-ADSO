<?php

declare(strict_types=1);

namespace App\Adapters\Security;

use App\Application\Port\Clock;
use App\Application\Port\TokenIssuer;
use App\Application\Shared\AuthenticatedUser;
use App\Application\Shared\IssuedToken;
use App\Domain\Shared\Role;

/**
 * JWT HS256 signed with the key from the environment (D-11).
 *
 * Three base64url pieces separated by dots; the signature is an HMAC of the
 * first two. It carries the subject and the role and nothing personal
 * (api-contract 4). Thirty lines is not worth a dependency.
 */
final readonly class JwtTokenIssuer implements TokenIssuer
{
    public function __construct(
        private Clock $clock,
        private string $secret,
        private int $ttlSeconds,
    ) {}

    public function issue(string $userId, Role $role): IssuedToken
    {
        $issuedAt = $this->clock->now()->getTimestamp();

        $header = $this->encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = $this->encode([
            'sub' => $userId,
            'role' => $role->value,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->ttlSeconds,
        ]);

        $token = $header.'.'.$payload;

        return new IssuedToken($token.'.'.$this->sign($token), $this->ttlSeconds);
    }

    public function verify(string $token): ?AuthenticatedUser
    {
        $pieces = explode('.', $token);

        if (count($pieces) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $pieces;

        // hash_equals compares in constant time: without it, an attacker could
        // recover a valid signature one byte at a time by measuring how long
        // each comparison takes to fail.
        if (! hash_equals($this->sign($header.'.'.$payload), $signature)) {
            return null;
        }

        $claims = json_decode($this->decode($payload), true);

        if (! is_array($claims) || ! isset($claims['sub'], $claims['role'], $claims['exp'])) {
            return null;
        }

        if ((int) $claims['exp'] <= $this->clock->now()->getTimestamp()) {
            return null;
        }

        $role = Role::tryFrom((string) $claims['role']);

        return $role === null ? null : new AuthenticatedUser((string) $claims['sub'], $role);
    }

    /** @param array<string, mixed> $data */
    private function encode(array $data): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
    }

    private function decode(string $value): string
    {
        $padded = str_pad(strtr($value, '-_', '+/'), (int) (ceil(strlen($value) / 4) * 4), '=');
        $decoded = base64_decode($padded, true);

        return $decoded === false ? '' : $decoded;
    }

    private function sign(string $token): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $token, $this->secret, true)), '+/', '-_'), '=');
    }
}
