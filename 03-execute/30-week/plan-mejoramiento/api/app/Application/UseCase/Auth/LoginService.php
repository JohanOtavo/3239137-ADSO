<?php

declare(strict_types=1);

namespace App\Application\UseCase\Auth;

use App\Application\Port\PasswordHasher;
use App\Application\Port\TokenIssuer;
use App\Application\Port\UserRepository;
use App\Domain\Exception\InvalidCredentials;
use App\Domain\User\User;

/** US-01 - Sign in. */
final readonly class LoginService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenIssuer $tokens,
    ) {}

    public function handle(string $username, string $password): LoginResult
    {
        $user = $this->users->findByUsername(User::normalizeUsername($username));

        // One message for an unknown user and for a wrong password: the API
        // must not become a way to find out which usernames exist (AC-01.2).
        if ($user === null || ! $this->hasher->matches($password, $user->passwordHash)) {
            throw new InvalidCredentials('Usuario o contraseña incorrectos.');
        }

        return new LoginResult($this->tokens->issue($user->id, $user->role), $user->role);
    }
}
