<?php

declare(strict_types=1);

namespace Tests\Support\Fake;

use App\Application\Port\IdGenerator;

/** Predictable identifiers, so a test can assert on them. */
final class SequentialIds implements IdGenerator
{
    private int $issued = 0;

    public function next(): string
    {
        $this->issued++;

        return 'id-'.$this->issued;
    }
}
