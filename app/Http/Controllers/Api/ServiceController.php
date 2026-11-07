<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        /*
         * The already-shaped payload is cached, not the models. A serialised
         * Eloquent object fails to come back when the worker reading it has
         * not loaded the class yet, which took the endpoint down with a 500
         * rather than simply missing the cache.
         */
        $services = Cache::remember('public_services', 300, function () {
            return Service::published()->ordered()->get()
                ->map(fn (Service $service) => (new ServiceResource($service))->resolve())
                ->all();
        });

        return response()->json(['data' => $services]);
    }
}
