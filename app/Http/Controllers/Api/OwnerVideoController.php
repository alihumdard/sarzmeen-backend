<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BlogStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Video;
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
            $this->scope($request)->orderBy('sort_order')->latest('id')->get()
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
            /*
             * Published outright. These are not editorial copy waiting on a
             * reviewer; an agent adding a walkthrough means to show it, and
             * a draft they could not publish would simply look broken.
             */
            'status' => BlogStatus::Published,
            'published_at' => now(),
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

        $video->title = $data['title'];
        $video->description = $data['description'] ?? '';
        $video->youtube_url = $data['youtubeUrl'];
        $video->youtube_id = $data['youtubeId'];
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
        ]);

        $id = Video::extractYoutubeId($data['youtubeUrl']);

        if ($id === null) {
            throw ValidationException::withMessages([
                'youtubeUrl' => 'That is not a YouTube link we can read. Paste the address from the video or its share button.',
            ]);
        }

        $data['youtubeId'] = $id;

        return $data;
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
