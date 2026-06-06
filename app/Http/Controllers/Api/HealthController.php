<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'unavailable';
        }

        try {
            if (extension_loaded('redis')) {
                Redis::ping();
                $checks['redis'] = 'ok';
            } else {
                $checks['redis'] = 'not_configured';
            }
        } catch (\Throwable $e) {
            $checks['redis'] = 'unavailable';
        }

        $allHealthy = ! in_array('unavailable', $checks);

        return ApiResponse::success([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'uptime' => round(microtime(true) - LARAVEL_START, 3) . 's',
        ], 'Health check complete', $allHealthy ? 200 : 503);
    }
}
