<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use Illuminate\Http\JsonResponse;

/**
 * T-01 - Liveness probe. Public, no token, no database.
 *
 * It must not touch the database: the container orchestration uses this to
 * decide whether the process is up, and a probe that fails when the database
 * is slow restarts a perfectly healthy application.
 */
final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
