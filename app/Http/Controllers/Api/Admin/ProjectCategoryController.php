<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectCategoryRequest;
use App\Http\Resources\ProjectCategoryResource;
use App\Models\Project;
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

    /**
     * `projects.project_category_id` has no cascade, so the database would
     * reject this with a raw constraint error. Count first and say why.
     */
    public function destroy(ProjectCategory $projectCategory): JsonResponse
    {
        $projectCount = Project::where('project_category_id', $projectCategory->id)->count();

        if ($projectCount > 0) {
            $noun = $projectCount === 1 ? 'project uses' : 'projects use';

            return response()->json([
                'message' => 'This category cannot be deleted because '
                    .$projectCount.' '.$noun.' it. Reassign those projects first.',
                'blocked_by' => ['projects' => $projectCount],
            ], 422);
        }

        $projectCategory->delete();
        Cache::forget('taxonomy_project_categories');

        return response()->json(null, 204);
    }
}
