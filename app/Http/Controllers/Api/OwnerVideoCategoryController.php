<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\VideoCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * An agent's or agency's own video categories.
 *
 * Theirs alone: an agency groups its videos the way its work is
 * organised, which is not how another agency or the site would. One
 * controller for both kinds, as with the videos themselves — they differ
 * only in which column holds the owner.
 */
class OwnerVideoCategoryController extends Controller
{
    /** How many groupings one owner may keep. */
    private const LIMIT = 15;

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->scope($request)
                ->ordered()
                ->withCount('videos')
                ->get()
                ->map(fn (VideoCategory $c) => $this->present($c))
                ->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $owner = $this->owner($request);

        if ($this->scope($request)->count() >= self::LIMIT) {
            throw ValidationException::withMessages([
                'name' => 'You can keep up to '.self::LIMIT.' categories. Remove one to add another.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $name = trim($data['name']);

        /* The same name twice is the same grouping, however it is cased. */
        $existing = $this->scope($request)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'name' => 'You already have a category by that name.',
            ]);
        }

        $category = VideoCategory::create([
            $owner['column'] => $owner['id'],
            'name' => $name,
            'slug' => VideoCategory::uniqueSlug($name, ...$this->ownerIds($request)),
            'sort_order' => ($this->scope($request)->max('sort_order') ?? -1) + 1,
        ]);

        return response()->json(['data' => $this->present($category)], 201);
    }

    public function update(Request $request, VideoCategory $category): JsonResponse
    {
        $this->authorise($request, $category);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $name = trim($data['name']);

        $clash = $this->scope($request)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->where('id', '!=', $category->id)
            ->exists();

        if ($clash) {
            throw ValidationException::withMessages([
                'name' => 'You already have a category by that name.',
            ]);
        }

        $category->name = $name;
        $category->slug = VideoCategory::uniqueSlug($name, ...[...$this->ownerIds($request), $category->id]);
        $category->save();

        return response()->json(['data' => $this->present($category)]);
    }

    public function destroy(Request $request, VideoCategory $category): JsonResponse
    {
        $this->authorise($request, $category);

        /* The videos stay; the column is nulled by the foreign key. A
           grouping removed should not take the work with it. */
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

    /** Which column holds this account's categories, and the id in it. */
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

    /** The pair the slug helper takes: agent id, then agency id. */
    private function ownerIds(Request $request): array
    {
        $owner = $this->owner($request);

        return $owner['column'] === 'agent_id'
            ? [$owner['id'], null]
            : [null, $owner['id']];
    }

    private function scope(Request $request)
    {
        $owner = $this->owner($request);

        return VideoCategory::where($owner['column'], $owner['id']);
    }

    /**
     * 404 rather than 403 for someone else's category: whether a given
     * id exists is not this account's business.
     */
    private function authorise(Request $request, VideoCategory $category): void
    {
        $owner = $this->owner($request);

        if ((int) $category->{$owner['column']} !== $owner['id']) {
            abort(404);
        }
    }
}
