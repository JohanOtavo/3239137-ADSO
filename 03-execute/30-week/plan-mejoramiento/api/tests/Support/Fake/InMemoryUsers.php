<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\UserRepository;
use App\Domain\User\User;

final class InMemoryUsers implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function find(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByUsername(string $username): ?User
    {
        foreach ($this->users as $user) {
            if ($user->username === $username) {
                return $user;
            }
        }

        return null;
    }

    public function existsByUsername(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function add(User $user): void
    {
        $this->users[$user->id] = $user;
    }
}
