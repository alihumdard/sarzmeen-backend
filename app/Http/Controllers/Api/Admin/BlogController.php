<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BlogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogRequest;
use App\Http\Resources\BlogResource;
use App\Http\Resources\BlogDetailResource;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class BlogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Blog::with(['category', 'author'])
            ->latest();

        if ($request->filled('status')) {
            $status = BlogStatus::tryFrom($request->string('status')->toString());
            if ($status) {
                $query->where('status', $status);
            }
        }

        return BlogResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Blog $blog): BlogDetailResource
    {
        $blog->load(['category', 'author', 'tags']);

        return new BlogDetailResource($blog);
    }

    public function store(BlogRequest $request): JsonResponse
    {
        $data = $this->mapInput($request->validated());
        $data['author_id'] = $request->user()->id;

        if ($data['status'] === BlogStatus::Published->value && ! isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        $blog = Blog::create($data);

        if (! empty($data['tag_ids'])) {
            $blog->tags()->sync($data['tag_ids']);
        }

        $blog->load(['category', 'author', 'tags']);

        Cache::forget('public_blogs');

        return (new BlogDetailResource($blog))
            ->response()
            ->setStatusCode(201);
    }

    public function update(BlogRequest $request, Blog $blog): BlogDetailResource
    {
        $data = $this->mapInput($request->validated());

        if (
            $data['status'] === BlogStatus::Published->value
            && ! $blog->published_at
        ) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        if (array_key_exists('tag_ids', $data)) {
            $blog->tags()->sync($data['tag_ids']);
        }

        $blog->load(['category', 'author', 'tags']);

        Cache::forget('public_blogs');

        return new BlogDetailResource($blog);
    }

    public function destroy(Blog $blog): JsonResponse
    {
        $blog->delete();

        Cache::forget('public_blogs');

        return response()->json(null, 204);
    }

    private function mapInput(array $data): array
    {
        if (isset($data['category'])) {
            $data['blog_category_id'] = BlogCategory::where('slug', $data['category'])->value('id');
            unset($data['category']);
        }

        if (isset($data['readTime'])) {
            $data['read_time'] = $data['readTime'];
            unset($data['readTime']);
        }

        if (isset($data['tags'])) {
            $data['tag_ids'] = BlogTag::whereIn('slug', $data['tags'])->pluck('id')->all();
            unset($data['tags']);
        }

        return $data;
    }
}
