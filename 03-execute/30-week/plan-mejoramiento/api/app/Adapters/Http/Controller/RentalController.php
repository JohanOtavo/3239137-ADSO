<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Middleware\AuthenticateToken;
use App\Adapters\Http\Request\Input;
use App\Adapters\Http\Request\PageLimits;
use App\Adapters\Http\Response\Presenter;
use App\Application\Port\Clock;
use App\Application\Shared\AuthenticatedUser;
use App\Application\UseCase\Rental\GetRentalService;
use App\Application\UseCase\Rental\ListOverdueService;
use App\Application\UseCase\Rental\RentEquipmentService;
use App\Application\UseCase\Rental\ReturnRentalItemService;
use App\Application\UseCase\Rental\SearchRentalsService;
use App\Domain\Rental\Rental;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US-06, US-07 and US-08 - Recording a rental, returning a line, consulting. */
final readonly class RentalController
{
    public function __construct(
        private PageLimits $pages,
        private RentEquipmentService $rent,
        private ReturnRentalItemService $return,
        private SearchRentalsService $search,
        private GetRentalService $get,
        private ListOverdueService $overdue,
        private Presenter $presenter,
        private Clock $clock,
    ) {}

    public function store(Request $request): JsonResponse
    {
        // Who recorded the rental comes from the token and never from the body:
        // reading it from the body would let a caller sign the rental as
        // somebody else (AC-06.8).
        $rental = $this->rent->handle(
            Input::requiredString($request, 'producer_id'),
            Input::requiredStringList($request, 'equipment_ids'),
            $this->actor($request)->id,
        );

        return new JsonResponse($this->presenter->rental($rental, $this->clock->now()), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->search->handle(
            Input::optionalInstant($request, 'from'),
            Input::optionalInstant($request, 'to'),
            Input::optionalStatus($request),
            Input::optionalString($request, 'producer_id'),
            $this->pages->of($request),
        );

        $now = $this->clock->now();

        return new JsonResponse(Presenter::page(
            $result,
            fn (Rental $rental): array => $this->presenter->rental($rental, $now),
        ));
    }

    public function show(string $id): JsonResponse
    {
        return new JsonResponse($this->presenter->rental($this->get->handle($id), $this->clock->now()));
    }

    public function returnItem(string $rentalId, string $itemId): JsonResponse
    {
        $rental = $this->return->handle($rentalId, $itemId);

        return new JsonResponse($this->presenter->rental($rental, $this->clock->now()));
    }

    public function overdue(): JsonResponse
    {
        return new JsonResponse([
            'items' => array_map([Presenter::class, 'overdue'], $this->overdue->handle()),
        ]);
    }

    private function actor(Request $request): AuthenticatedUser
    {
        $user = $request->attributes->get(AuthenticateToken::ATTRIBUTE);

        // The middleware guarantees this; the assertion makes the guarantee
        // visible rather than leaving a silent null if a route forgets it.
        assert($user instanceof AuthenticatedUser);

        return $user;
    }
}
