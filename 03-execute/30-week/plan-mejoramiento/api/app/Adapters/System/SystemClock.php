<?php

declare(strict_types=1);

namespace App\Adapters\System;

use App\Application\Port\Clock;
use DateTimeImmutable;
use DateTimeZone;

/** The real clock, always in UTC (BR-6). */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        // The timezone is set explicitly rather than trusting the process
        // default: a container started with a different TZ would otherwise
        // store instants that are hours off.
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
