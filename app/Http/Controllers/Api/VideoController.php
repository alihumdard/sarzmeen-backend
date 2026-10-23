<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $videos = Video::published()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($w) => $w->where('title', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term));
            })
            // Featured first, then the order the admin set, then newest.
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 12));

        return VideoResource::collection($videos);
    }

    public function show(string $slug): VideoResource
    {
        $video = Video::published()->where('slug', $slug)->firstOrFail();

        $video->increment('views');

        return new VideoResource($video);
    }

    /** Sidebar on a video page: other videos to watch next. */
    public function related(string $slug): AnonymousResourceCollection
    {
        $videos = Video::published()
            ->where('slug', '!=', $slug)
            ->orderByDesc('featured')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return VideoResource::collection($videos);
    }
}
