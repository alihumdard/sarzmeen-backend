<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Cache::remember('taxonomy_categories', 300, function () {
            return Category::with('parent')
                ->withCount(['propertyTypes', 'properties'])
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

    /**
     * Categories cascade: `property_types.category_id` is `cascadeOnDelete`, so
     * removing a category silently takes every type under it with no way back.
     * Child categories would also be orphaned (`parent_id` is `nullOnDelete`).
     *
     * So refuse while anything still depends on this category and say exactly
     * what is in the way. Emptying it first is a deliberate act; losing 18
     * property types to one click is not.
     */
    public function destroy(Category $category): JsonResponse
    {
        $typeCount = $category->propertyTypes()->count();
        $childCount = $category->children()->count();

        $propertyCount = Property::query()
            ->whereIn('property_type_id', $category->propertyTypes()->select('id'))
            ->count();

        if ($typeCount > 0 || $childCount > 0 || $propertyCount > 0) {
            return response()->json([
                'message' => $this->blockedMessage($typeCount, $childCount, $propertyCount),
                'blocked_by' => [
                    'property_types' => $typeCount,
                    'child_categories' => $childCount,
                    'properties' => $propertyCount,
                ],
            ], 422);
        }

        $category->delete();
        $this->clearCache();

        return response()->json(null, 204);
    }

    private function blockedMessage(int $types, int $children, int $properties): string
    {
        $parts = [];

        if ($types > 0) {
            $parts[] = $types.' property '.($types === 1 ? 'type' : 'types');
        }

        if ($children > 0) {
            $parts[] = $children.' sub-'.($children === 1 ? 'category' : 'categories');
        }

        if ($properties > 0) {
            $parts[] = $properties.' '.($properties === 1 ? 'listing' : 'listings');
        }

        return 'This category still has '.$this->joinParts($parts)
            .'. Move or delete them first, then delete the category.';
    }

    private function joinParts(array $parts): string
    {
        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);

        return implode(', ', $parts).' and '.$last;
    }

    private function clearCache(): void
    {
        Cache::forget('taxonomy_categories');
        Cache::forget('taxonomy_property_types');
    }
}
