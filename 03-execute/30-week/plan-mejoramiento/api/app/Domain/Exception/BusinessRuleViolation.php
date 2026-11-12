<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Root of every broken business rule.
 *
 * The domain knows nothing about HTTP: it names the contract code and carries a
 * Spanish message, because that message is read by the person using the
 * application. Turning the code into a status is the adapter's job.
 */
abstract class BusinessRuleViolation extends RuntimeException
{
    /** @param list<array{field: string, issue: string}> $details */
    public function __construct(string $message, private readonly array $details = [])
    {
        parent::__construct($message);
    }

    abstract public function code(): string;

    /** @return list<array{field: string, issue: string}> */
    public function details(): array
    {
        return $this->details;
    }
}
