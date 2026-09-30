<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Http\Resources\BlogDetailResource;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Blog::published()
            ->with(['category', 'author'])
            ->latest('published_at');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')->toString()));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->string('tag')->toString()));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('excerpt', 'ilike', "%{$search}%");
            });
        }

        return BlogResource::collection(
            $query->paginate($request->integer('per_page', 9))
        );
    }

    public function show(string $slug): BlogDetailResource
    {
        $blog = Blog::published()
            ->where('slug', $slug)
            ->with(['category', 'author', 'tags'])
            ->firstOrFail();

        return new BlogDetailResource($blog);
    }

    public function categories(): JsonResponse
    {
        $categories = BlogCategory::withCount(['blogs' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();

        return BlogCategoryResource::collection($categories)->response();
    }

    public function tags(): JsonResponse
    {
        $tags = BlogTag::orderBy('name')->get();

        return response()->json([
            'data' => $tags->map(fn ($tag) => [
                'id' => (string) $tag->id,
                'slug' => $tag->slug,
                'name' => $tag->name,
            ]),
        ]);
    }
}
