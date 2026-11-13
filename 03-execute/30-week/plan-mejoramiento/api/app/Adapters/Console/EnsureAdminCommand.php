<?php

declare(strict_types=1);

namespace App\Adapters\Console;

use App\Application\Port\IdGenerator;
use App\Application\Port\PasswordHasher;
use App\Application\Port\UserRepository;
use App\Domain\Shared\Role;
use App\Domain\User\User;
use Illuminate\Console\Command;

/**
 * T-17 - Provisions the first administrator from the environment.
 *
 * The admin role is never granted through the API (AC-02.3), so there has to be
 * another way in, and this is it. The command is idempotent: running it twice,
 * or on every deployment, leaves one administrator and changes no password.
 *
 * The plain password is read from the environment and only ever passed to the
 * hashing port. It is not logged, not echoed and not written to the database.
 */
final class EnsureAdminCommand extends Command
{
    protected $signature = 'filmgear:ensure-admin';

    protected $description = 'Creates the administrator declared in the environment, if it does not exist yet';

    public function handle(
        UserRepository $users,
        PasswordHasher $hasher,
        IdGenerator $ids,
    ): int {
        $username = trim((string) config('filmgear.admin_username'));
        $password = (string) config('filmgear.admin_password');

        if ($username === '' || $password === '') {
            $this->error('ADMIN_USERNAME and ADMIN_PASSWORD must both be set.');

            return self::FAILURE;
        }

        $normalized = User::normalizeUsername($username);

        if ($users->existsByUsername($normalized)) {
            $this->info(sprintf('The administrator %s already exists; nothing to do.', $normalized));

            return self::SUCCESS;
        }

        $users->add(User::register(
            $ids->next(),
            $normalized,
            $hasher->hash($password),
            Role::Admin,
        ));

        $this->info(sprintf('Administrator %s created.', $normalized));

        return self::SUCCESS;
    }
}
