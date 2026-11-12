<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/** AC-06.4 - The producer reached the limit of equipment held at once. */
final class ProducerLimitExceeded extends BusinessRuleViolation
{
    public function code(): string
    {
        return 'PRODUCER_LIMIT_EXCEEDED';
    }
}
