<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoRequest;
use App\Http\Resources\VideoResource;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $videos = Video::query()
            /* For the owner column; without these the list queries per row. */
            ->with(['agent.user', 'agency'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';
                /*
                 * Tags too. They are deliberately not shown anywhere —
                 * the owner writes them for finding a video by, the way
                 * YouTube's are — so searching is the only thing they
                 * can be for.
                 */
                $q->where(fn ($w) => $w->where('title', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term)
                    ->orWhereRaw('tags::text ilike ?', [$term]));
            })
            ->orderBy('sort_order')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return VideoResource::collection($videos);
    }

    public function show(Video $video): VideoResource
    {
        return new VideoResource($video);
    }

    public function store(VideoRequest $request): JsonResponse
    {
        $video = Video::create($this->attributes($request));

        return (new VideoResource($video))
            ->response()
            ->setStatusCode(201);
    }

    public function update(VideoRequest $request, Video $video): VideoResource
    {
        $video->update($this->attributes($request, $video));

        return new VideoResource($video->fresh());
    }

    /**
     * Approve an owner's video, putting it on their page.
     *
     * Only a video waiting for review: approving one already published
     * would reset the date it went up, and the site's own guides never
     * enter review at all.
     */
    public function approve(Request $request, Video $video): JsonResponse
    {
        if ($video->status !== VideoStatus::Pending) {
            abort(422, 'That video is not waiting for review.');
        }

        $video->status = VideoStatus::Published;
        $video->published_at = now();
        $video->review_note = null;
        $video->reviewed_at = now();
        $video->reviewed_by = $request->user()->id;
        $video->save();

        return response()->json(['data' => (new VideoResource($video))->resolve($request)]);
    }

    /** Turn one down, with a reason its owner can read. */
    public function reject(Request $request, Video $video): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $video->status = VideoStatus::Rejected;
        $video->published_at = null;
        $video->review_note = trim($data['note']);
        $video->reviewed_at = now();
        $video->reviewed_by = $request->user()->id;
        $video->save();

        return response()->json(['data' => (new VideoResource($video))->resolve($request)]);
    }

    public function destroy(Video $video): JsonResponse
    {
        if ($video->thumbnail) {
            $this->imageService->delete($video->thumbnail);
        }

        $video->delete();

        return response()->json(null, 204);
    }

    /**
     * Maps the request onto the column names, deriving what can be derived.
     *
     * @return array<string, mixed>
     */
    /** One of the site's own categories, or none. */
    private function categoryId(array $data): ?int
    {
        if (empty($data['categoryId'])) {
            return null;
        }

        return VideoCategory::editorial()->where('id', $data['categoryId'])->value('id');
    }

    /** Trimmed, blanks dropped, and no repeats however they are cased. */
    private function tags(array $data): array
    {
        return collect($data['tags'] ?? [])
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique(fn (string $tag) => mb_strtolower($tag))
            ->take(10)
            ->values()
            ->all();
    }

    private function attributes(VideoRequest $request, ?Video $existing = null): array
    {
        $data = $request->validated();
        $status = VideoStatus::from($data['status']);

        $attributes = [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'youtube_url' => $data['youtubeUrl'],
            'youtube_id' => Video::extractYoutubeId($data['youtubeUrl']),
            'status' => $status,
            'featured' => (bool) ($data['featured'] ?? false),
            /* Only the site's own categories: an agency's grouping is
               theirs, and a guide filed under it would appear to belong
               to them. */
            'video_category_id' => $this->categoryId($data),
            'tags' => $this->tags($data),
            'sort_order' => (int) ($data['sortOrder'] ?? 0),
            'meta_title' => $data['metaTitle'] ?? null,
            'meta_description' => $data['metaDescription'] ?? null,
        ];

        if ($request->hasFile('thumbnailFile')) {
            // Replace rather than accumulate orphaned files.
            if ($existing?->thumbnail) {
                $this->imageService->delete($existing->thumbnail);
            }

            $attributes['thumbnail'] = $this->imageService->upload(
                $request->file('thumbnailFile'),
                'videos',
            );
        } elseif (array_key_exists('thumbnail', $data)) {
            $attributes['thumbnail'] = $data['thumbnail'];
        }

        /*
         * Stamp the publish date the first time it goes live, and keep it
         * afterwards — re-publishing an edited video should not reorder it
         * above everything written since.
         */
        if ($status === VideoStatus::Published && ! $existing?->published_at) {
            $attributes['published_at'] = now();
        }

        return $attributes;
    }
}
