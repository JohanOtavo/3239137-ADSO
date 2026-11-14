<?php

declare(strict_types=1);

namespace Tests\Architecture\Fixtures\ForbiddenImport;

use Illuminate\Support\Collection;

/**
 * A fixture, not production code: a domain class that reaches for the
 * framework, which article I forbids.
 *
 * It exists so the architecture check can be seen to fail. A check that has
 * only ever passed proves that it runs, not that it looks at anything.
 */
final class DomainWithFramework
{
    public function __construct(public Collection $items) {}
}
