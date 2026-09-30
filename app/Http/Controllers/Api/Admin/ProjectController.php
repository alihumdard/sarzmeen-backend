<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Http\Resources\ProjectDetailResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::with(['images', 'category', 'location.parent'])
            ->latest();

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->input('category')));
        }

        return ProjectResource::collection($query->paginate(15));
    }

    public function show(Project $project): ProjectDetailResource
    {
        $project->load([
            'images', 'category', 'location.parent',
            'amenities', 'paymentPlans', 'highlights', 'plotSizes',
        ]);

        return new ProjectDetailResource($project);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $project = Project::create([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'project_category_id' => $data['projectCategoryId'],
                'developer' => $data['developer'],
                'location_id' => $data['locationId'],
                'full_location' => $data['fullLocation'] ?? '',
                'status' => $data['status'],
                'price_from' => $data['priceFrom'] ?? '',
                'total_area' => $data['totalArea'] ?? '',
                'total_units' => $data['totalUnits'] ?? null,
                'description' => $data['description'] ?? '',
                'verified' => $data['verified'] ?? false,
                'featured' => $data['featured'] ?? false,
            ]);

            $this->syncRelations($project, $data);

            return $project;
        });

        $project->load(['images', 'category', 'location.parent', 'amenities', 'paymentPlans', 'highlights', 'plotSizes']);

        return (new ProjectDetailResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProjectRequest $request, Project $project): ProjectDetailResource
    {
        DB::transaction(function () use ($request, $project) {
            $data = $request->validated();

            $project->update([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'project_category_id' => $data['projectCategoryId'],
                'developer' => $data['developer'],
                'location_id' => $data['locationId'],
                'full_location' => $data['fullLocation'] ?? '',
                'status' => $data['status'],
                'price_from' => $data['priceFrom'] ?? '',
                'total_area' => $data['totalArea'] ?? '',
                'total_units' => $data['totalUnits'] ?? null,
                'description' => $data['description'] ?? '',
                'verified' => $data['verified'] ?? false,
                'featured' => $data['featured'] ?? false,
            ]);

            $this->syncRelations($project, $data);
        });

        $project->load(['images', 'category', 'location.parent', 'amenities', 'paymentPlans', 'highlights', 'plotSizes']);

        return new ProjectDetailResource($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        foreach ($project->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $project->delete();

        return response()->json(null, 204);
    }

    private function syncRelations(Project $project, array $data): void
    {
        if (array_key_exists('amenities', $data)) {
            $project->amenities()->delete();
            if (! empty($data['amenities'])) {
                $project->amenities()->createMany(
                    array_map(fn (string $name) => ['name' => $name], $data['amenities'])
                );
            }
        }

        if (array_key_exists('highlights', $data)) {
            $project->highlights()->delete();
            if (! empty($data['highlights'])) {
                $project->highlights()->createMany(
                    array_map(fn (string $text) => ['text' => $text], $data['highlights'])
                );
            }
        }

        if (array_key_exists('plotSizes', $data)) {
            $project->plotSizes()->delete();
            if (! empty($data['plotSizes'])) {
                $project->plotSizes()->createMany(
                    array_map(fn (string $size) => ['size' => $size], $data['plotSizes'])
                );
            }
        }

        if (array_key_exists('paymentPlan', $data)) {
            $project->paymentPlans()->delete();
            if (! empty($data['paymentPlan'])) {
                foreach ($data['paymentPlan'] as $i => $plan) {
                    $project->paymentPlans()->create([
                        'label' => $plan['label'],
                        'value' => $plan['value'],
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        if (! empty($data['images'])) {
            $maxSort = $project->images()->max('sort_order') ?? -1;
            foreach ($data['images'] as $index => $image) {
                $path = $image->store("projects/{$project->id}", 'public');
                $project->images()->create([
                    'path' => $path,
                    'is_cover' => $project->images()->count() === 0 && $index === 0,
                    'sort_order' => $maxSort + $index + 1,
                ]);
            }
        }
    }
}
