<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\Clock;
use DateTimeImmutable;

/** A clock that does not move, except when the test moves it. */
final class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $moment) {}

    public function now(): DateTimeImmutable
    {
        return $this->moment;
    }

    public function travelTo(string $moment): void
    {
        $this->moment = new DateTimeImmutable($moment);
    }
}
