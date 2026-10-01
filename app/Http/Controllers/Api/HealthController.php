<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
            $healthy = false;
        }

        try {
            Cache::store()->put('health_check', true, 10);
            Cache::store()->forget('health_check');
            $checks['cache'] = 'ok';
        } catch (\Throwable) {
            $checks['cache'] = 'error';
            $healthy = false;
        }

        try {
            Storage::disk('public')->exists('.');
            $checks['storage'] = 'ok';
        } catch (\Throwable) {
            $checks['storage'] = 'error';
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
