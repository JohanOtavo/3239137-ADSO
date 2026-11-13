<?php

declare(strict_types=1);

namespace App\Application\Port;

/** UUID v4 comes from the application, never from the database. */
interface IdGenerator
{
    public function next(): string;
}
