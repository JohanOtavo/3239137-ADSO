<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Auth\LoginService;
use App\Domain\Exception\InvalidCredentials;
use App\Domain\Shared\Role;
use App\Domain\User\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\InMemoryUsers;
use Tests\Support\Fake\PredictableTokenIssuer;
use Tests\Support\Fake\ReversiblePasswordHasher;

/** T-07 - US-01 against doubles. */
final class LoginServiceTest extends TestCase
{
    private InMemoryUsers $users;

    private LoginService $service;

    protected function setUp(): void
    {
        $this->users = new InMemoryUsers();
        $this->users->add(User::register('user-1', 'ana', 'hashed:secreta', Role::Advisor));

        $this->service = new LoginService(
            $this->users,
            new ReversiblePasswordHasher(),
            new PredictableTokenIssuer(),
        );
    }

    public function test_valid_credentials_return_a_token_and_the_role(): void
    {
        $result = $this->service->handle('ana', 'secreta');

        $this->assertSame('token-for-user-1-as-advisor', $result->token->accessToken);
        $this->assertSame(28800, $result->token->expiresInSeconds);
        $this->assertSame(Role::Advisor, $result->role);
    }

    public function test_the_username_is_normalised_before_the_lookup(): void
    {
        $result = $this->service->handle('  ANA ', 'secreta');

        $this->assertSame(Role::Advisor, $result->role);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->service->handle('ana', 'otra-clave');
    }

    public function test_an_unknown_user_is_rejected(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->service->handle('nadie', 'secreta');
    }

    /** AC-01.2 - the two failures must be indistinguishable from outside. */
    public function test_an_unknown_user_and_a_wrong_password_give_the_same_message(): void
    {
        $unknownUser = null;
        $wrongPassword = null;

        try {
            $this->service->handle('nadie', 'secreta');
        } catch (InvalidCredentials $failure) {
            $unknownUser = $failure->getMessage();
        }

        try {
            $this->service->handle('ana', 'otra-clave');
        } catch (InvalidCredentials $failure) {
            $wrongPassword = $failure->getMessage();
        }

        $this->assertSame($unknownUser, $wrongPassword);
        $this->assertNotNull($unknownUser);
    }
}
