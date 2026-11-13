<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\UseCase\Auth\CreateAdvisorService;
use App\Domain\Exception\UsernameTaken;
use App\Domain\Exception\ValidationFailed;
use App\Domain\Shared\Role;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fake\InMemoryUsers;
use Tests\Support\Fake\ReversiblePasswordHasher;
use Tests\Support\Fake\SequentialIds;

/** T-07 - US-02 against doubles. */
final class CreateAdvisorServiceTest extends TestCase
{
    private InMemoryUsers $users;

    private CreateAdvisorService $service;

    protected function setUp(): void
    {
        $this->users = new InMemoryUsers();
        $this->service = new CreateAdvisorService($this->users, new ReversiblePasswordHasher(), new SequentialIds());
    }

    public function test_it_creates_an_advisor_with_a_normalised_username(): void
    {
        $user = $this->service->handle('Ana ', 'clave-de-prueba', 'advisor');

        $this->assertSame('ana', $user->username);
        $this->assertSame(Role::Advisor, $user->role);
        $this->assertNotNull($this->users->findByUsername('ana'));
    }

    public function test_it_stores_the_hash_and_never_the_plain_password(): void
    {
        $user = $this->service->handle('ana', 'clave-de-prueba', 'advisor');

        $this->assertSame('hashed:clave-de-prueba', $user->passwordHash);
        $this->assertStringNotContainsString('clave-de-prueba', $user->username);
    }

    public function test_a_duplicate_username_is_rejected(): void
    {
        $this->service->handle('ana', 'clave-de-prueba', 'advisor');

        $this->expectException(UsernameTaken::class);

        $this->service->handle('ANA', 'otra-clave', 'advisor');
    }

    public function test_the_api_cannot_create_an_admin(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->service->handle('root', 'clave-de-prueba', 'admin');
    }

    public function test_asking_for_admin_creates_nothing(): void
    {
        try {
            $this->service->handle('root', 'clave-de-prueba', 'admin');
            $this->fail('Expected ValidationFailed.');
        } catch (ValidationFailed) {
            $this->assertNull($this->users->findByUsername('root'));
        }
    }
}
