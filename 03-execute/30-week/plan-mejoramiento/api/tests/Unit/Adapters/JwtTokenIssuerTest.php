<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters;

use App\Adapters\Security\JwtTokenIssuer;
use App\Domain\Shared\Role;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\FixedClock;

/** T-14 - Issuing and verifying the session token. */
final class JwtTokenIssuerTest extends TestCase
{
    private FixedClock $clock;

    private JwtTokenIssuer $issuer;

    protected function setUp(): void
    {
        $this->clock = new FixedClock(new DateTimeImmutable('2026-11-13T10:00:00Z'));
        $this->issuer = new JwtTokenIssuer($this->clock, 'a-long-enough-test-secret', 28800);
    }

    public function test_it_issues_a_token_that_verifies_back(): void
    {
        $token = $this->issuer->issue('user-1', Role::Advisor);

        $user = $this->issuer->verify($token->accessToken);

        $this->assertNotNull($user);
        $this->assertSame('user-1', $user->id);
        $this->assertSame(Role::Advisor, $user->role);
    }

    public function test_the_token_lasts_eight_hours(): void
    {
        $this->assertSame(28800, $this->issuer->issue('user-1', Role::Admin)->expiresInSeconds);
    }

    public function test_it_has_three_pieces(): void
    {
        $token = $this->issuer->issue('user-1', Role::Admin)->accessToken;

        $this->assertCount(3, explode('.', $token));
    }

    /** api-contract 4 - nothing personal travels inside the token. */
    public function test_it_carries_no_personal_data(): void
    {
        $token = $this->issuer->issue('user-1', Role::Advisor)->accessToken;
        $payload = base64_decode(strtr(explode('.', $token)[1], '-_', '+/'), true);

        $claims = json_decode((string) $payload, true);

        $this->assertSame(['sub', 'role', 'iat', 'exp'], array_keys($claims));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $token = $this->issuer->issue('user-1', Role::Advisor);

        $this->clock->travelTo('2026-11-13T18:00:01Z');

        $this->assertNull($this->issuer->verify($token->accessToken));
    }

    public function test_a_token_signed_with_another_key_is_rejected(): void
    {
        $other = new JwtTokenIssuer($this->clock, 'a-different-secret', 28800);

        $forged = $other->issue('user-1', Role::Admin);

        $this->assertNull($this->issuer->verify($forged->accessToken));
    }

    /** Changing the role in the payload must break the signature. */
    public function test_a_tampered_payload_is_rejected(): void
    {
        $token = $this->issuer->issue('user-1', Role::Advisor)->accessToken;
        [$header, $payload, $signature] = explode('.', $token);

        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/'), true), true);
        $claims['role'] = 'admin';
        $tampered = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        $this->assertNull($this->issuer->verify($header.'.'.$tampered.'.'.$signature));
    }

    public function test_a_malformed_token_is_rejected(): void
    {
        $this->assertNull($this->issuer->verify('not-a-token'));
        $this->assertNull($this->issuer->verify(''));
        $this->assertNull($this->issuer->verify('a.b'));
    }
}
