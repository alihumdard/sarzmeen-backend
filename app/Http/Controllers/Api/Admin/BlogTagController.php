<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlogTagRequest;
use App\Http\Resources\BlogTagResource;
use App\Models\BlogTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogTagController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tags = BlogTag::withCount('blogs')
            ->orderBy('name')
            ->get();

        return BlogTagResource::collection($tags);
    }

    public function store(BlogTagRequest $request): JsonResponse
    {
        $tag = BlogTag::create($request->validated());
        $tag->loadCount('blogs');

        return (new BlogTagResource($tag))
            ->response()
            ->setStatusCode(201);
    }

    public function update(BlogTagRequest $request, BlogTag $blogTag): BlogTagResource
    {
        $blogTag->update($request->validated());
        $blogTag->loadCount('blogs');

        return new BlogTagResource($blogTag);
    }

    /**
     * Deleting a tag only detaches it from posts through the pivot — the posts
     * themselves survive — so this is allowed while a tag is in use. The
     * confirmation in the UI reports the count so the choice is informed.
     */
    public function destroy(BlogTag $blogTag): JsonResponse
    {
        $blogTag->blogs()->detach();
        $blogTag->delete();

        return response()->json(null, 204);
    }
}
