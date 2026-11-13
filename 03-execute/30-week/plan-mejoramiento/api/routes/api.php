<?php

declare(strict_types=1);

use App\Adapters\Http\Controller\AuthController;
use App\Adapters\Http\Controller\CategoryController;
use App\Adapters\Http\Controller\EquipmentController;
use App\Adapters\Http\Controller\EquipmentPhotoController;
use App\Adapters\Http\Controller\HealthController;
use App\Adapters\Http\Controller\ProducerController;
use App\Adapters\Http\Controller\RentalController;
use App\Adapters\Http\Controller\ReportController;
use Illuminate\Support\Facades\Route;

/*
 * Routes from docs/api-contract.md, served under /api/v1.
 *
 * Only the login and the health probe are public. Everything else goes through
 * the token and, where the contract says so, through a role (AC-01.3). The
 * allowed roles are written next to the path on purpose: the authorisation
 * rules of the whole API can be read off this one file.
 */

Route::get('health', HealthController::class);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('token')->group(function (): void {
    // Anyone with a token may read the catalogue; only an admin may change it.
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('equipment', [EquipmentController::class, 'index']);
    Route::get('equipment/{id}', [EquipmentController::class, 'show']);
    Route::get('equipment/{id}/photo', [EquipmentPhotoController::class, 'show']);

    Route::middleware('role:admin')->group(function (): void {
        Route::post('users', [AuthController::class, 'createAdvisor']);
        Route::post('equipment', [EquipmentController::class, 'store']);
        Route::put('equipment/{id}', [EquipmentController::class, 'update']);
        Route::delete('equipment/{id}', [EquipmentController::class, 'destroy']);
        Route::put('equipment/{id}/photo', [EquipmentPhotoController::class, 'update']);
        Route::delete('equipment/{id}/photo', [EquipmentPhotoController::class, 'destroy']);
    });

    Route::middleware('role:admin,advisor')->group(function (): void {
        Route::get('producers', [ProducerController::class, 'index']);
        Route::get('producers/{id}', [ProducerController::class, 'show']);
        Route::post('producers', [ProducerController::class, 'store']);

        // Declared before rentals/{id} so the literal path wins over the
        // parameter: otherwise "overdue" is read as a rental identifier.
        Route::get('rentals/overdue', [RentalController::class, 'overdue']);
        Route::post('rentals', [RentalController::class, 'store']);
        Route::get('rentals', [RentalController::class, 'index']);
        Route::get('rentals/{id}', [RentalController::class, 'show']);
        Route::post('rentals/{rentalId}/items/{itemId}/return', [RentalController::class, 'returnItem']);

        Route::get('reports/most-requested', [ReportController::class, 'mostRequested']);
    });
});
