<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogCategoryRequest;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = BlogCategory::withCount('blogs')
            ->orderBy('name')
            ->get();

        return BlogCategoryResource::collection($categories);
    }

    public function store(BlogCategoryRequest $request): JsonResponse
    {
        $category = BlogCategory::create($request->validated());
        $category->loadCount('blogs');

        return (new BlogCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(BlogCategoryRequest $request, BlogCategory $blogCategory): BlogCategoryResource
    {
        $blogCategory->update($request->validated());
        $blogCategory->loadCount('blogs');

        return new BlogCategoryResource($blogCategory);
    }

    /**
     * `blogs.blog_category_id` is nullOnDelete, so removing a category in use
     * would silently leave those posts uncategorised. Refuse and say how many
     * would be affected, matching the other taxonomy guards.
     */
    public function destroy(BlogCategory $blogCategory): JsonResponse
    {
        $blogCount = $blogCategory->blogs()->count();

        if ($blogCount > 0) {
            $noun = $blogCount === 1 ? 'post uses' : 'posts use';

            return response()->json([
                'message' => 'This category cannot be deleted because '
                    .$blogCount.' '.$noun.' it. Move those posts first.',
                'blocked_by' => ['blogs' => $blogCount],
            ], 422);
        }

        $blogCategory->delete();

        return response()->json(null, 204);
    }
}
