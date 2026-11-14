<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;

/**
 * T-12 - Base class for the tests that need the real engine.
 *
 * These boot the application the same way the server does, through
 * bootstrap/app.php, so the routes, the middleware aliases, the exception
 * handlers and the port bindings under test are the ones that ship. A test
 * that replaced any of them would prove nothing about the deployment.
 *
 * The schema is rebuilt before every test and not wrapped in a transaction.
 * Wrapping would be faster, but the concurrency test needs two connections
 * that can see each other's committed rows, and an open transaction hides
 * them. One correct base is worth more than two almost identical ones.
 */
abstract class IntegrationTestCase extends FoundationTestCase
{
    /** The five fixed category identifiers (data-model 9). */
    protected const CAMERAS = '11111111-1111-4111-8111-111111111111';

    protected const LENSES = '22222222-2222-4222-8222-222222222222';

    /** A secret for the test process alone; deployment reads its own. */
    private const TEST_SECRET = 'integration-test-secret-not-for-deployment';

    private const ADMIN_USERNAME = 'root';

    private const ADMIN_PASSWORD = 'admin-password-for-tests';

    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // The composition root refuses to boot without a signing secret, so the
        // test supplies one instead of depending on the machine's environment.
        $app->make('config')->set('filmgear.jwt_secret', self::TEST_SECRET);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->emptyTheEngine();
        $this->runCommand('migrate');
    }

    /**
     * Drops the project schema instead of asking migrate:fresh to wipe it.
     *
     * The wipe lists the tables of the search path, and the session that opened
     * before the schema existed does not always see them, which showed up as an
     * intermittent "relation category already exists". Dropping the schema is
     * one statement and leaves the engine in the state ADR-001 assumes: empty,
     * with the migration owning every table.
     */
    private function emptyTheEngine(): void
    {
        $connection = $this->app->make('db')->connection();

        $connection->statement('drop schema if exists filmgear cascade');
        $connection->statement('drop table if exists public.migrations');
    }

    /**
     * Provisions the administrator the way a deployment does, through the
     * command, and returns a token for it. Inserting the row by hand would
     * skip the one path that can produce an admin (AC-02.3).
     */
    protected function signInAsAdmin(): string
    {
        config([
            'filmgear.admin_username' => self::ADMIN_USERNAME,
            'filmgear.admin_password' => self::ADMIN_PASSWORD,
        ]);

        $this->runCommand('filmgear:ensure-admin');

        return $this->tokenOf(self::ADMIN_USERNAME, self::ADMIN_PASSWORD);
    }

    /**
     * Runs a command through the console kernel.
     *
     * Not the artisan test helper: that one builds its expectations with
     * Mockery, which this project does not install, so the helper fails before
     * the command runs.
     */
    private function runCommand(string $command): void
    {
        $exitCode = $this->app->make(Kernel::class)->call($command);

        self::assertSame(0, $exitCode, sprintf('The command %s failed.', $command));
    }

    protected function tokenOf(string $username, string $password): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);

        $response->assertStatus(200);

        return (string) $response->json('access_token');
    }

    /** @return array<string, string> */
    protected function bearer(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token];
    }
}
