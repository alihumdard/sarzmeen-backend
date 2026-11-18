<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageViewLog;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The public video library.
 *
 * Everybody's videos, not only the site's: an agency's walkthrough sits
 * beside a guide the site wrote, and carries its owner's name so a
 * viewer can go from one video to the rest of that agency's. They stay
 * on the owner's own page as well — this is a second place to find them,
 * not a move.
 *
 * An owner's video reaches here only once an administrator has approved
 * it, which `published()` already means.
 */
class VideoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $videos = Video::published()
            ->with(['category', 'agent.user', 'agency'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';

                /* Tags as well: they are written for finding a video by
                   and are shown nowhere, so search is all they are for. */
                $q->where(fn ($w) => $w->where('title', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term)
                    ->orWhereRaw('tags::text ilike ?', [$term]));
            })
            ->when($request->filled('category'), function ($q) use ($request) {
                $q->whereHas('category', fn ($c) => $c->where('slug', $request->input('category')));
            })
            /* The site's own first, then an owner's: a visitor who came
               for a guide should not have to scroll past a dozen agency
               walkthroughs to reach one. */
            ->orderByRaw('case when agent_id is null and agency_id is null then 0 else 1 end')
            ->orderByDesc('featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 12));

        return VideoResource::collection($videos);
    }

    /**
     * Every category with a published video in it, grouped by its owner.
     *
     * Grouped rather than one long row: ten agencies with five groupings
     * each is fifty chips, and a visitor looking for the site's own
     * guides would have to read all of them first. The site's come back
     * first, under their own heading.
     *
     * A category nobody has a published video in is left out — it would
     * lead to an empty page.
     */
    public function categories(): JsonResponse
    {
        $categories = VideoCategory::query()
            ->whereHas('videos', fn ($q) => $q->published())
            ->withCount(['videos' => fn ($q) => $q->published()])
            ->with(['agent.user', 'agency'])
            ->ordered()
            ->get();

        $groups = [];

        foreach ($categories as $category) {
            [$key, $label] = match (true) {
                $category->agency_id !== null => [
                    'agency-'.$category->agency_id,
                    $category->agency?->name ?? 'An agency',
                ],
                $category->agent_id !== null => [
                    'agent-'.$category->agent_id,
                    $category->agent?->user?->name ?? 'An agent',
                ],
                default => ['sarzameen', 'Sarzameen'],
            };

            $groups[$key] ??= ['key' => $key, 'label' => $label, 'categories' => []];

            $groups[$key]['categories'][] = [
                'id' => (string) $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'videoCount' => (int) $category->videos_count,
            ];
        }

        /* The site's own group first; the rest follow in name order. */
        $own = $groups['sarzameen'] ?? null;
        unset($groups['sarzameen']);

        $rest = collect($groups)->sortBy('label')->values()->all();

        return response()->json([
            'data' => $own ? [$own, ...$rest] : $rest,
        ]);
    }

    public function show(string $slug): VideoResource
    {
        $video = Video::published()
            ->with(['category', 'agent.user', 'agency'])
            ->where('slug', $slug)
            ->firstOrFail();

        $video->increment('views');

        /* The running total was kept but never dated, so there was no way
           to say when a guide was watched. */
        PageViewLog::record('video', $video->id);

        return new VideoResource($video);
    }

    /** Sidebar on a video page: other videos to watch next. */
    public function related(string $slug): AnonymousResourceCollection
    {
        $videos = Video::published()
            ->with(['category', 'agent.user', 'agency'])
            ->where('slug', '!=', $slug)
            ->orderByDesc('featured')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return VideoResource::collection($videos);
    }
}
