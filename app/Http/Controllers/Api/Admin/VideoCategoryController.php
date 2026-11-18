<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The site's own video categories.
 *
 * Only the site's: an agency's groupings are theirs to manage from their
 * own section, and a guide filed under one would appear to belong to
 * them.
 */
class VideoCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = VideoCategory::editorial()
            ->ordered()
            ->withCount('videos')
            ->get()
            ->map(fn (VideoCategory $c) => $this->present($c));

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $name = trim($request->validate([
            'name' => ['required', 'string', 'max:80'],
        ])['name']);

        if ($this->nameTaken($name)) {
            throw ValidationException::withMessages([
                'name' => 'There is already a category by that name.',
            ]);
        }

        $category = VideoCategory::create([
            'name' => $name,
            'slug' => VideoCategory::uniqueSlug($name, null, null),
            'sort_order' => (VideoCategory::editorial()->max('sort_order') ?? -1) + 1,
        ]);

        return response()->json(['data' => $this->present($category)], 201);
    }

    public function update(Request $request, VideoCategory $category): JsonResponse
    {
        $this->authorise($category);

        $name = trim($request->validate([
            'name' => ['required', 'string', 'max:80'],
        ])['name']);

        if ($this->nameTaken($name, $category->id)) {
            throw ValidationException::withMessages([
                'name' => 'There is already a category by that name.',
            ]);
        }

        $category->name = $name;
        $category->slug = VideoCategory::uniqueSlug($name, null, null, $category->id);
        $category->save();

        return response()->json(['data' => $this->present($category)]);
    }

    public function destroy(VideoCategory $category): JsonResponse
    {
        $this->authorise($category);

        /* The videos stay; the column is nulled by the foreign key. */
        $category->delete();

        return response()->json(null, 204);
    }

    private function present(VideoCategory $category): array
    {
        return [
            'id' => (string) $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'videoCount' => (int) ($category->videos_count ?? 0),
        ];
    }

    /** The same name twice is the same grouping, however it is cased. */
    private function nameTaken(string $name, ?int $ignoreId = null): bool
    {
        return VideoCategory::editorial()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * 404 rather than 403 for an owner's category: it is not the admin
     * section's to edit, and saying it exists adds nothing.
     */
    private function authorise(VideoCategory $category): void
    {
        if ($category->agent_id !== null || $category->agency_id !== null) {
            abort(404);
        }
    }
}
