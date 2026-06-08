<?php

namespace App\Providers;

use App\Events\ProjectCreated;
use App\Listeners\AddOwnerAsProjectMember;
use App\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\Repositories\ProjectRepositoryInterface::class,
            \App\Repositories\ProjectRepository::class,
        );
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerEvents();
    }

    protected function registerEvents(): void
    {
        Event::listen(
            ProjectCreated::class,
            AddOwnerAsProjectMember::class,
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) env('RATE_LIMIT_API', 60))
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return ApiResponse::error('Too many requests. Please slow down.', 429);
                });
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute((int) env('RATE_LIMIT_AUTH', 10))
                ->by($request->ip())
                ->response(function () {
                    return ApiResponse::error('Too many authentication attempts. Try again in a minute.', 429);
                });
        });

        RateLimiter::for('writes', function (Request $request) {
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                return Limit::perMinute((int) env('RATE_LIMIT_WRITES', 30))
                    ->by($request->user()?->id ?: $request->ip())
                    ->response(fn () => ApiResponse::error('Write rate limit exceeded.', 429));
            }
            return Limit::none();
        });
    }
}
