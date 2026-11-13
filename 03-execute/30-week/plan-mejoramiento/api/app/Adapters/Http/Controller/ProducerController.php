<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Request\Input;
use App\Adapters\Http\Request\PageLimits;
use App\Adapters\Http\Response\Presenter;
use App\Application\UseCase\Producer\CreateProducerService;
use App\Application\UseCase\Producer\GetProducerService;
use App\Application\UseCase\Producer\SearchProducersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US-05 - Producers, with the count of equipment still out on the detail. */
final readonly class ProducerController
{
    public function __construct(
        private PageLimits $pages,
        private SearchProducersService $search,
        private GetProducerService $get,
        private CreateProducerService $create,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->search->handle(
            Input::optionalString($request, 'q'),
            $this->pages->of($request),
        );

        return new JsonResponse(Presenter::page(
            $result,
            [Presenter::class, 'producer'],
        ));
    }

    public function show(string $id): JsonResponse
    {
        $detail = $this->get->handle($id);

        return new JsonResponse(Presenter::producer(
            $detail['producer'],
            $detail['open_rentals'],
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $producer = $this->create->handle(
            Input::requiredString($request, 'full_name'),
            Input::requiredString($request, 'document_number'),
        );

        return new JsonResponse(Presenter::producer($producer), 201);
    }
}
