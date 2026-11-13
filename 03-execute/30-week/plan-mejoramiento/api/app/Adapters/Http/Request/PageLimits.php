<?php

declare(strict_types=1);

namespace App\Adapters\Http\Request;

use App\Application\Shared\Pagination;
use Illuminate\Http\Request;

/**
 * The page limits of the contract, read from config and not written twice.
 *
 * Each controller used to carry its own pair of constants, which is how one
 * list ends up capped at fifty and another at a hundred without anybody
 * deciding so.
 */
final readonly class PageLimits
{
    public function __construct(
        private int $maxSize,
        private int $defaultSize,
    ) {}

    public function of(Request $request): Pagination
    {
        return Pagination::of(
            Input::optionalInt($request, 'page'),
            Input::optionalInt($request, 'size'),
            $this->maxSize,
            $this->defaultSize,
        );
    }
}
