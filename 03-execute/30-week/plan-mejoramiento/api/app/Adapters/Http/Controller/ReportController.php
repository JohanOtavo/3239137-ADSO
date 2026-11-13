<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Request\Input;
use App\Adapters\Http\Response\Presenter;
use App\Application\UseCase\Report\MostRequestedReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US-10 - Most requested equipment over a period (AC-10.1). */
final readonly class ReportController
{
    public function __construct(private MostRequestedReportService $report) {}

    public function mostRequested(Request $request): JsonResponse
    {
        // Both dates are required: a report over an open-ended period would
        // read the whole history and say nothing useful about a season.
        $rows = $this->report->handle(
            Input::requiredInstant($request, 'from'),
            Input::requiredInstant($request, 'to'),
        );

        return new JsonResponse([
            'items' => array_map([Presenter::class, 'mostRequested'], $rows),
        ]);
    }
}
