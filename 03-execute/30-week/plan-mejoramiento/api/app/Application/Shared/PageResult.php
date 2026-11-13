<?php

declare(strict_types=1);

namespace App\Application\Shared;

/**
 * A page of results with its total (AC-04.2).
 *
 * @template T
 */
final readonly class PageResult
{
    /** @param list<T> $items */
    public function __construct(
        public array $items,
        public int $page,
        public int $size,
        public int $total,
    ) {}
}
