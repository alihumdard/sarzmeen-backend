<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::ordered()->get());
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->validated());

        $this->clearCache();

        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service);
    }

    public function update(ServiceRequest $request, Service $service): ServiceResource
    {
        $service->update($request->validated());

        $this->clearCache();

        return new ServiceResource($service);
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        $this->clearCache();

        return response()->json(null, 204);
    }

    private function clearCache(): void
    {
        Cache::forget('public_services');
    }
}
