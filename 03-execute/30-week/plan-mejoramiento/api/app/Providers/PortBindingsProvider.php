<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Http\Request\PageLimits;
use App\Adapters\Persistence\Database\DatabaseCategoryRepository;
use App\Adapters\Persistence\Database\DatabaseEquipmentRepository;
use App\Adapters\Persistence\Database\DatabaseProducerRepository;
use App\Adapters\Persistence\Database\DatabaseRentalRepository;
use App\Adapters\Persistence\Database\DatabaseTransactionManager;
use App\Adapters\Persistence\Database\DatabaseUserRepository;
use App\Adapters\Persistence\Database\SqlMostRequestedQuery;
use App\Adapters\Security\JwtTokenIssuer;
use App\Adapters\Security\NativePasswordHasher;
use App\Adapters\Storage\DiskPhotoStorage;
use App\Adapters\System\RandomUuidGenerator;
use App\Adapters\System\SystemClock;
use App\Application\Port\CategoryRepository;
use App\Application\Port\Clock;
use App\Application\Port\EquipmentRepository;
use App\Application\Port\IdGenerator;
use App\Application\Port\MostRequestedQuery;
use App\Application\Port\PasswordHasher;
use App\Application\Port\PhotoStorage;
use App\Application\Port\ProducerRepository;
use App\Application\Port\RentalRepository;
use App\Application\Port\TokenIssuer;
use App\Application\Port\TransactionManager;
use App\Application\Port\UserRepository;
use App\Application\UseCase\Equipment\SetEquipmentPhotoService;
use App\Domain\Shared\RentalPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * The composition root: the one file that names both a port and an adapter
 * (article IV).
 *
 * Nothing else in the application mentions a concrete adapter, which is what
 * the architecture test checks. Swapping storage or the token format is a
 * change to this file alone.
 */
final class PortBindingsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ConnectionInterface::class, static fn (): ConnectionInterface => DB::connection());

        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(IdGenerator::class, RandomUuidGenerator::class);
        $this->app->singleton(PasswordHasher::class, NativePasswordHasher::class);

        $this->bindPolicy();
        $this->bindPersistence();
        $this->bindSecurity();
        $this->bindStorage();
        $this->bindHttp();
    }

    private function bindPolicy(): void
    {
        // The business numbers come from config and are read once, so a change
        // to the rental window is a deployment change and not a code change.
        $this->app->singleton(RentalPolicy::class, static fn (): RentalPolicy => new RentalPolicy(
            (int) config('filmgear.rental_days'),
            (int) config('filmgear.max_open_equipment_per_producer'),
            (int) config('filmgear.max_items_per_rental'),
        ));
    }

    private function bindPersistence(): void
    {
        $this->app->bind(TransactionManager::class, DatabaseTransactionManager::class);
        $this->app->bind(CategoryRepository::class, DatabaseCategoryRepository::class);
        $this->app->bind(UserRepository::class, DatabaseUserRepository::class);
        $this->app->bind(ProducerRepository::class, DatabaseProducerRepository::class);
        $this->app->bind(EquipmentRepository::class, DatabaseEquipmentRepository::class);
        $this->app->bind(RentalRepository::class, DatabaseRentalRepository::class);
        $this->app->bind(MostRequestedQuery::class, SqlMostRequestedQuery::class);
    }

    private function bindSecurity(): void
    {
        $this->app->singleton(TokenIssuer::class, function (Application $app): TokenIssuer {
            $secret = (string) config('filmgear.jwt_secret');

            // Refusing to boot without a secret is deliberate. A default secret
            // would sign tokens anybody could forge, and the failure would be
            // silent until somebody noticed.
            if ($secret === '') {
                throw new RuntimeException('JWT_SECRET is not set; the application refuses to start.');
            }

            return new JwtTokenIssuer(
                $app->make(Clock::class),
                $secret,
                (int) config('filmgear.token_ttl_seconds'),
            );
        });
    }

    private function bindStorage(): void
    {
        $this->app->singleton(PhotoStorage::class, static fn (Application $app): PhotoStorage => new DiskPhotoStorage(
            Storage::disk('photos'),
            $app->make(IdGenerator::class),
        ));

        $this->app->bind(SetEquipmentPhotoService::class, static fn (Application $app): SetEquipmentPhotoService => new SetEquipmentPhotoService(
            $app->make(EquipmentRepository::class),
            $app->make(PhotoStorage::class),
            (int) config('filmgear.photo_max_bytes'),
            (array) config('filmgear.photo_mime_types'),
        ));
    }

    private function bindHttp(): void
    {
        $this->app->singleton(PageLimits::class, static fn (): PageLimits => new PageLimits(
            (int) config('filmgear.page_size_max'),
            (int) config('filmgear.page_size_default'),
        ));
    }
}
