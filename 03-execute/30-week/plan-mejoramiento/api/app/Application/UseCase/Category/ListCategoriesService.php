<?php

declare(strict_types=1);

namespace App\Application\UseCase\Category;

use App\Application\Port\CategoryRepository;
use App\Application\Shared\CategoryView;

/** The five categories, ordered by name (api-contract 3.3). */
final readonly class ListCategoriesService
{
    public function __construct(private CategoryRepository $categories) {}

    /** @return list<CategoryView> */
    public function handle(): array
    {
        return $this->categories->all();
    }
}
