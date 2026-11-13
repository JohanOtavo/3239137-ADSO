<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Request\Input;
use App\Adapters\Http\Request\PageLimits;
use App\Adapters\Http\Response\Presenter;
use App\Application\UseCase\Equipment\CreateEquipmentService;
use App\Application\UseCase\Equipment\GetEquipmentService;
use App\Application\UseCase\Equipment\SearchEquipmentService;
use App\Application\UseCase\Equipment\SoftDeleteEquipmentService;
use App\Application\UseCase\Equipment\UpdateEquipmentService;
use App\Domain\Equipment\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US-03 and US-04 - The catalogue: search, detail, create, replace, retire. */
final readonly class EquipmentController
{
    public function __construct(
        private PageLimits $pages,
        private SearchEquipmentService $search,
        private GetEquipmentService $get,
        private CreateEquipmentService $create,
        private UpdateEquipmentService $update,
        private SoftDeleteEquipmentService $softDelete,
        private Presenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->search->handle(
            Input::optionalString($request, 'q'),
            Input::optionalString($request, 'category_id'),
            $this->pages->of($request),
        );

        return new JsonResponse(Presenter::page(
            $result,
            fn (Equipment $equipment): array => $this->presenter->equipment($equipment),
        ));
    }

    public function show(string $id): JsonResponse
    {
        return new JsonResponse($this->presenter->equipment($this->get->handle($id)));
    }

    public function store(Request $request): JsonResponse
    {
        // available_units is not read from the request: the server computes it
        // from the total, so a client cannot hand itself extra stock.
        $equipment = $this->create->handle(
            Input::requiredString($request, 'name'),
            Input::requiredString($request, 'brand'),
            Input::requiredString($request, 'model_code'),
            Input::requiredString($request, 'category_id'),
            Input::requiredInt($request, 'total_units'),
        );

        return new JsonResponse($this->presenter->equipment($equipment), 201);
    }

    /** model_code is absent on purpose: it identifies the equipment and is not editable. */
    public function update(Request $request, string $id): JsonResponse
    {
        $equipment = $this->update->handle(
            $id,
            Input::requiredString($request, 'name'),
            Input::requiredString($request, 'brand'),
            Input::requiredString($request, 'category_id'),
            Input::requiredInt($request, 'total_units'),
        );

        return new JsonResponse($this->presenter->equipment($equipment));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->softDelete->handle($id);

        return new JsonResponse(null, 204);
    }
}
