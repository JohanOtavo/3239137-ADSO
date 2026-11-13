<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\TransactionManager;

/** Runs the work as is: with no database there is nothing to commit. */
final class InstantTransactions implements TransactionManager
{
    public function transactional(callable $work): mixed
    {
        return $work();
    }
}
