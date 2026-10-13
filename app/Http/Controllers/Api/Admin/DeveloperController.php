<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeveloperRequest;
use App\Models\Developer;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Resources\DeveloperResource;

class DeveloperController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return DeveloperResource::collection(
            Developer::withCount('projects')->orderBy('name')->get(),
        );
    }

    public function show(Developer $developer): DeveloperResource
    {
        $developer->loadCount('projects');

        return new DeveloperResource($developer);
    }

    public function store(DeveloperRequest $request): JsonResponse
    {
        $developer = Developer::create($this->payload($request));

        return (new DeveloperResource($developer))->response()->setStatusCode(201);
    }

    public function update(DeveloperRequest $request, Developer $developer): DeveloperResource
    {
        $developer->update($this->payload($request, $developer));

        return new DeveloperResource($developer->loadCount('projects'));
    }

    public function destroy(Developer $developer): JsonResponse
    {
        // Projects keep their `developer` name; the foreign key nulls out.
        $developer->delete();

        return response()->json(null, 204);
    }

    private function payload(DeveloperRequest $request, ?Developer $existing = null): array
    {
        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'verified' => $data['verified'] ?? false,
        ];

        if ($request->hasFile('logo')) {
            if ($existing?->logo) {
                $this->imageService->delete($existing->logo);
            }
            $payload['logo'] = $this->imageService->upload($request->file('logo'), 'developers');
        }

        return $payload;
    }
}
