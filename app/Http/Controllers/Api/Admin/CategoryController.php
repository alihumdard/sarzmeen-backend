<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Cache::remember('taxonomy_categories', 300, function () {
            return Category::with('parent')
                ->orderByDesc('featured')
                ->orderBy('name')
                ->get();
        });

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        $category->load('parent');

        return new CategoryResource($category);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['parent'])) {
            $data['parent_id'] = Category::where('slug', $data['parent'])->value('id');
        }
        unset($data['parent']);

        $category = Category::create($data);
        $category->load('parent');

        $this->clearCache();

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $data = $request->validated();

        if (array_key_exists('parent', $data)) {
            $data['parent_id'] = $data['parent']
                ? Category::where('slug', $data['parent'])->value('id')
                : null;
        }
        unset($data['parent']);

        $category->update($data);
        $category->load('parent');

        $this->clearCache();

        return new CategoryResource($category);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        $this->clearCache();

        return response()->json(null, 204);
    }

    private function clearCache(): void
    {
        Cache::forget('taxonomy_categories');
        Cache::forget('taxonomy_property_types');
    }
}
