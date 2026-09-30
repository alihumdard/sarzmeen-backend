<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectCategoryRequest;
use App\Http\Resources\ProjectCategoryResource;
use App\Models\ProjectCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ProjectCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Cache::remember('taxonomy_project_categories', 300, function () {
            return ProjectCategory::query()
                ->orderByDesc('featured')
                ->orderBy('name')
                ->get();
        });

        return ProjectCategoryResource::collection($categories);
    }

    public function show(ProjectCategory $projectCategory): ProjectCategoryResource
    {
        return new ProjectCategoryResource($projectCategory);
    }

    public function store(ProjectCategoryRequest $request): JsonResponse
    {
        $category = ProjectCategory::create($request->validated());

        Cache::forget('taxonomy_project_categories');

        return (new ProjectCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProjectCategoryRequest $request, ProjectCategory $projectCategory): ProjectCategoryResource
    {
        $projectCategory->update($request->validated());

        Cache::forget('taxonomy_project_categories');

        return new ProjectCategoryResource($projectCategory);
    }

    public function destroy(ProjectCategory $projectCategory): JsonResponse
    {
        $projectCategory->delete();
        Cache::forget('taxonomy_project_categories');

        return response()->json(null, 204);
    }
}
