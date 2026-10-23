<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BlogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoRequest;
use App\Http\Resources\VideoResource;
use App\Models\Video;
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
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($w) => $w->where('title', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term));
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
    private function attributes(VideoRequest $request, ?Video $existing = null): array
    {
        $data = $request->validated();
        $status = BlogStatus::from($data['status']);

        $attributes = [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'youtube_url' => $data['youtubeUrl'],
            'youtube_id' => Video::extractYoutubeId($data['youtubeUrl']),
            'status' => $status,
            'featured' => (bool) ($data['featured'] ?? false),
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
        if ($status === BlogStatus::Published && ! $existing?->published_at) {
            $attributes['published_at'] = now();
        }

        return $attributes;
    }
}
