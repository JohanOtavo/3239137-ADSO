<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Shared\CategoryView;

/** Five seeded rows, read only. There is no create, update or delete. */
interface CategoryRepository
{
    /** @return list<CategoryView> */
    public function all(): array;

    public function find(string $id): ?CategoryView;
}
