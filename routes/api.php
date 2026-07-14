<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'check'])
    ->name('health');

Route::prefix('v1')->name('v1.')->group(function () {

    Route::prefix('auth')->name('auth.')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/profile', [AuthController::class, 'profile'])->name('auth.profile');
        Route::put('/auth/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');

        Route::apiResource('projects', ProjectController::class)
            ->middleware('throttle:writes');

        Route::apiResource('projects.tasks', TaskController::class)
            ->shallow()
            ->middleware('throttle:writes');

        Route::apiResource('tasks.comments', CommentController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->shallow()
            ->middleware('throttle:writes');

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::patch('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
            Route::patch('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        });

        Route::get('/users', [UserController::class, 'index'])->name('users.index');

        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    });
});
