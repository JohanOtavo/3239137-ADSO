<?php

declare(strict_types=1);

use App\Adapters\Http\Controller\HealthController;
use Illuminate\Support\Facades\Route;

/*
 * Routes from docs/api-contract.md, served under /api/v1.
 *
 * Only the login and the health probe are public. Everything else goes through
 * the token and, where the contract says so, through a role (CA-01.3).
 */

Route::get('health', HealthController::class);
