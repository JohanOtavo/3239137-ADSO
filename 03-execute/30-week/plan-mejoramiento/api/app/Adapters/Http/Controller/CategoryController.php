<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Response\Presenter;
use App\Application\UseCase\Category\ListCategoriesService;
use Illuminate\Http\JsonResponse;

/** US-09 - The five fixed categories, for the filters (AC-09.2). */
final readonly class CategoryController
{
    public function __construct(private ListCategoriesService $categories) {}

    public function index(): JsonResponse
    {
        return new JsonResponse([
            'items' => array_map([Presenter::class, 'category'], $this->categories->handle()),
        ]);
    }
}
