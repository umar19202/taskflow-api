<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'check'])
    ->name('health');

Route::prefix('v1')->name('v1.')->group(function () {

    Route::prefix('auth')->name('auth.')->middleware('throttle:auth')->group(function () {
        // Auth routes will be added in Phase 2
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Protected routes will be added in subsequent phases
    });
});
