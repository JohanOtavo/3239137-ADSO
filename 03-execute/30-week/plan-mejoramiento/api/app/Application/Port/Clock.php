<?php

declare(strict_types=1);

namespace App\Application\Port;

use DateTimeImmutable;

/**
 * The clock is a port (D-05).
 *
 * It is what lets a test check a four day term without waiting four days, and
 * what lets the demo seeder record a rental that is already overdue.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
