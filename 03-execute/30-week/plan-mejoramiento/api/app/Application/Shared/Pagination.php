<?php

declare(strict_types=1);

namespace App\Application\Shared;

/**
 * The page being asked for.
 *
 * The size is clamped to the contract maximum rather than rejected: asking for
 * 500 returns 50 (AC-04.1). A client that over-asks gets data, not an error.
 */
final readonly class Pagination
{
    private function __construct(
        public int $page,
        public int $size,
    ) {}

    public static function of(?int $page, ?int $size, int $maxSize, int $defaultSize): self
    {
        return new self(
            max(1, $page ?? 1),
            min($maxSize, max(1, $size ?? $defaultSize)),
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->size;
    }
}
