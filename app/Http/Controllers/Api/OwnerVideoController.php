<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Video;
use App\Models\VideoCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * An agent's or agency's own videos.
 *
 * One controller for both: the two differ only in which column holds the
 * owner, and keeping them apart would mean the same rules written twice
 * and drifting apart the first time one of them changed.
 *
 * These never reach the public library — see Video::scopeEditorial.
 */
class OwnerVideoController extends Controller
{
    /** How many a single owner may keep. */
    private const LIMIT = 20;

    public function index(Request $request): AnonymousResourceCollection
    {
        return VideoResource::collection(
            $this->scope($request)->with('category')->orderBy('sort_order')->latest('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $owner = $this->owner($request);

        if ($this->scope($request)->count() >= self::LIMIT) {
            throw ValidationException::withMessages([
                'youtubeUrl' => 'You can keep up to '.self::LIMIT.' videos. Remove one to add another.',
            ]);
        }

        $data = $this->validated($request);

        $video = new Video([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'youtube_url' => $data['youtubeUrl'],
            'youtube_id' => $data['youtubeId'],
            'link' => $data['link'] ?? null,
            'tags' => $data['tags'] ?? [],
            'video_category_id' => $this->categoryId($request, $data),
            /*
             * Waiting for an administrator. These go on a public page
             * under the site's name, so somebody looks before they do.
             */
            'status' => VideoStatus::Pending,
            'sort_order' => ($this->scope($request)->max('sort_order') ?? -1) + 1,
        ]);

        $video->slug = $this->uniqueSlug($data['title']);
        $video->{$owner['column']} = $owner['id'];
        $video->save();

        return response()->json(
            ['data' => (new VideoResource($video))->resolve($request)],
            201,
        );
    }

    public function update(Request $request, Video $video): JsonResponse
    {
        $this->authorise($request, $video);

        $data = $this->validated($request);

        /*
         * A published video edited goes back for review, because what was
         * approved is not what would now be shown. Only when the video or
         * where it leads changes — renaming one is not a new video.
         */
        $changedMaterially = $video->youtube_id !== $data['youtubeId']
            || $video->link !== ($data['link'] ?? null);

        $video->title = $data['title'];
        $video->description = $data['description'] ?? '';
        $video->youtube_url = $data['youtubeUrl'];
        $video->youtube_id = $data['youtubeId'];
        $video->link = $data['link'] ?? null;
        $video->tags = $data['tags'] ?? [];
        $video->video_category_id = $this->categoryId($request, $data);

        if ($changedMaterially && $video->status === VideoStatus::Published) {
            $video->status = VideoStatus::Pending;
            $video->published_at = null;
            $video->review_note = null;
        }

        /* A rejected video resubmitted is waiting again, and the old
           note no longer describes it. */
        if ($video->status === VideoStatus::Rejected) {
            $video->status = VideoStatus::Pending;
            $video->review_note = null;
        }

        $video->save();

        return response()->json([
            'data' => (new VideoResource($video))->resolve($request),
        ]);
    }

    public function destroy(Request $request, Video $video): JsonResponse
    {
        $this->authorise($request, $video);

        $video->delete();

        return response()->json(null, 204);
    }

    /**
     * The fields, plus the YouTube id pulled out of whatever link was
     * pasted. A link nothing can be read from is rejected here rather than
     * saved as a video that will never play.
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'youtubeUrl' => ['required', 'string', 'max:500'],
            /* Where the video leads — the listing it walks through, the
               project it shows. http and https only: a javascript: or
               data: address here would run in the viewer's browser. */
            'link' => ['nullable', 'url:http,https', 'max:500'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['required', 'string', 'max:40'],
            'categoryId' => ['nullable', 'integer'],
        ]);

        if (isset($data['tags'])) {
            /* Trimmed, blanks dropped, and no repeats — the form sends
               what was typed, and the same word twice is one tag. */
            $data['tags'] = collect($data['tags'])
                ->map(fn (string $tag) => trim($tag))
                ->filter()
                ->unique(fn (string $tag) => mb_strtolower($tag))
                ->take(10)
                ->values()
                ->all();
        }

        $id = Video::extractYoutubeId($data['youtubeUrl']);

        if ($id === null) {
            throw ValidationException::withMessages([
                'youtubeUrl' => 'That is not a YouTube link we can read. Paste the address from the video or its share button.',
            ]);
        }

        $data['youtubeId'] = $id;

        return $data;
    }

    /**
     * The category to file this video under, or null.
     *
     * Checked against this account's own categories: an id belonging to
     * another agency would otherwise file a video under their grouping.
     */
    private function categoryId(Request $request, array $data): ?int
    {
        if (empty($data['categoryId'])) {
            return null;
        }

        $owner = $this->owner($request);

        $category = VideoCategory::where('id', $data['categoryId'])
            ->where($owner['column'], $owner['id'])
            ->first();

        return $category?->id;
    }

    /** Which column holds this account's videos, and the id in it. */
    private function owner(Request $request): array
    {
        $user = $request->user();

        if ($user->hasRole('agent')) {
            $agent = Agent::where('user_id', $user->id)->firstOrFail();

            return ['column' => 'agent_id', 'id' => $agent->id];
        }

        $agency = Agency::where('user_id', $user->id)->firstOrFail();

        return ['column' => 'agency_id', 'id' => $agency->id];
    }

    /** This account's videos and no one else's. */
    private function scope(Request $request)
    {
        $owner = $this->owner($request);

        return Video::where($owner['column'], $owner['id']);
    }

    /**
     * 404 rather than 403 for someone else's video: whether a given id
     * exists is not this account's business.
     */
    private function authorise(Request $request, Video $video): void
    {
        $owner = $this->owner($request);

        if ((int) $video->{$owner['column']} !== $owner['id']) {
            abort(404);
        }
    }

    /** Slugs are unique across every video, editorial ones included. */
    private function uniqueSlug(string $title): string
    {
        $base = str($title)->slug()->value() ?: 'video';
        $slug = $base;

        while (Video::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.str()->random(5);
        }

        return $slug;
    }
}
