<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BlogStatus;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Project;
use App\Models\Property;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $q = $request->string('q')->toString();
        $limit = $request->integer('limit', 5);

        $properties = Property::where('status', PropertyStatus::Published)
            ->whereRaw(
                "to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, '') || ' ' || coalesce(full_location, '')) @@ plainto_tsquery('english', ?)",
                [$q],
            )
            ->with(['images', 'location'])
            ->limit($limit)
            ->get()
            ->map(fn (Property $p) => [
                'type' => 'property',
                'id' => (string) $p->id,
                'slug' => $p->slug,
                'title' => $p->title,
                'location' => $p->full_location,
                'price' => (float) $p->price,
                'image' => $this->imageService->url(
                    $p->images->firstWhere('is_cover', true)?->path ?? $p->images->first()?->path ?? '',
                ),
            ]);

        $projects = Project::whereRaw(
                "to_tsvector('english', coalesce(name, '') || ' ' || coalesce(description, '') || ' ' || coalesce(full_location, '')) @@ plainto_tsquery('english', ?)",
                [$q],
            )
            ->with(['images'])
            ->limit($limit)
            ->get()
            ->map(fn (Project $p) => [
                'type' => 'project',
                'id' => (string) $p->id,
                'slug' => $p->slug,
                'title' => $p->name,
                'location' => $p->full_location,
                'image' => $this->imageService->url(
                    $p->images->firstWhere('is_cover', true)?->path ?? $p->images->first()?->path ?? '',
                ),
            ]);

        $blogs = Blog::where('status', BlogStatus::Published)
            ->whereRaw(
                "to_tsvector('english', coalesce(title, '') || ' ' || coalesce(excerpt, '') || ' ' || coalesce(content, '')) @@ plainto_tsquery('english', ?)",
                [$q],
            )
            ->limit($limit)
            ->get()
            ->map(fn (Blog $b) => [
                'type' => 'blog',
                'id' => (string) $b->id,
                'slug' => $b->slug,
                'title' => $b->title,
                'excerpt' => mb_substr($b->excerpt, 0, 120),
                'image' => $this->imageService->url($b->image ?? ''),
            ]);

        return response()->json([
            'data' => [
                'properties' => $properties->values(),
                'projects' => $projects->values(),
                'blogs' => $blogs->values(),
            ],
        ]);
    }
}
