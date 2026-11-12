<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-06.5 - The producer is holding something overdue. */
final class ProducerHasOverdue extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'PRODUCER_HAS_OVERDUE';
    }
}
