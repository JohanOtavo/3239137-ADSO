<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Role;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;

/** T-06 - Username normalisation and role handling (AC-02.2, AC-02.3). */
final class UserTest extends TestCase
{
    public function test_the_username_is_trimmed_and_lowercased(): void
    {
        $user = User::register('user-1', 'Ana ', 'fake-hash', Role::Advisor);

        $this->assertSame('ana', $user->username);
    }

    public function test_two_spellings_normalise_to_the_same_username(): void
    {
        $first = User::register('user-1', 'Ana ', 'fake-hash', Role::Advisor);
        $second = User::register('user-2', 'ANA', 'fake-hash', Role::Advisor);

        $this->assertSame($first->username, $second->username);
    }

    public function test_an_empty_username_is_rejected(): void
    {
        $this->expectException(ValidationFailed::class);

        User::register('user-1', '   ', 'fake-hash', Role::Advisor);
    }

    public function test_a_user_needs_a_password_hash(): void
    {
        $this->expectException(ValidationFailed::class);

        User::register('user-1', 'ana', '', Role::Advisor);
    }

    public function test_it_knows_whether_it_is_an_admin(): void
    {
        $this->assertTrue(User::register('user-1', 'root', 'fake-hash', Role::Admin)->isAdmin());
        $this->assertFalse(User::register('user-2', 'ana', 'fake-hash', Role::Advisor)->isAdmin());
    }

    public function test_the_api_cannot_grant_the_admin_role(): void
    {
        $this->assertSame(Role::Advisor, Role::assignableByApi('advisor'));

        $this->expectException(ValidationFailed::class);

        Role::assignableByApi('admin');
    }

    public function test_an_unknown_role_is_rejected(): void
    {
        $this->expectException(ValidationFailed::class);

        Role::fromString('superadmin');
    }
}
