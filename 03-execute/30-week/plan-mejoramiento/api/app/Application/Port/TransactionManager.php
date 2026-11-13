<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * Taking the unit and creating the line are one operation (data-model 4).
 *
 * The use case asks for that guarantee here and never learns which engine
 * provides it.
 */
interface TransactionManager
{
    /** @param callable(): mixed $work */
    public function transactional(callable $work): mixed;
}
