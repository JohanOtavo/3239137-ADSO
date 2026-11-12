<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/** Derived from the lines and the clock: there is no column (article VII, AC-08.3). */
enum RentalStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Closed = 'closed';
}
