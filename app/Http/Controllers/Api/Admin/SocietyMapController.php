<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BlogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocietyMapRequest;
use App\Http\Resources\SocietyMapResource;
use App\Models\Location;
use App\Models\SocietyMap;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Mews\Purifier\Facades\Purifier;

/**
 * Society maps, which only an administrator manages.
 */
class SocietyMapController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $maps = SocietyMap::with(['location', 'city'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = BlogStatus::tryFrom($request->string('status')->toString());
                if ($status) {
                    $q->where('status', $status->value);
                }
            })
            ->when($request->filled('city'), function ($q) use ($request) {
                $q->whereHas('city', fn ($c) => $c->where('slug', $request->string('city')->toString()));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->toString().'%';
                $q->where('title', 'ilike', $term);
            })
            ->ordered()
            ->paginate($request->integer('per_page', 20));

        return SocietyMapResource::collection($maps);
    }

    public function show(SocietyMap $societyMap): SocietyMapResource
    {
        $societyMap->load(['location', 'city', 'blogs']);

        return new SocietyMapResource($societyMap);
    }

    public function store(SocietyMapRequest $request): JsonResponse
    {
        $data = $this->prepare($request);

        $data['image'] = $this->images->upload(
            $request->file('imageFile'),
            'society-maps',
            /* No resize: the detail a reader zooms in for is exactly what
               shrinking throws away. */
            null,
        );

        if ($request->hasFile('documentFile')) {
            $data['document'] = $this->images->upload(
                $request->file('documentFile'),
                'society-maps',
                null,
            );
        }

        $map = SocietyMap::create($data);

        $map->blogs()->sync($request->input('blogIds', []));
        $map->load(['location', 'city', 'blogs']);

        return (new SocietyMapResource($map))->response()->setStatusCode(201);
    }

    public function update(SocietyMapRequest $request, SocietyMap $societyMap): SocietyMapResource
    {
        $data = $this->prepare($request, $societyMap);

        if ($request->hasFile('imageFile')) {
            if ($societyMap->image) {
                $this->images->delete($societyMap->image);
            }

            $data['image'] = $this->images->upload(
                $request->file('imageFile'),
                'society-maps',
                null,
            );
        }

        if ($request->hasFile('documentFile')) {
            if ($societyMap->document) {
                $this->images->delete($societyMap->document);
            }

            $data['document'] = $this->images->upload(
                $request->file('documentFile'),
                'society-maps',
                null,
            );
        } elseif ($request->boolean('removeDocument') && $societyMap->document) {
            $this->images->delete($societyMap->document);
            $data['document'] = null;
        }

        $societyMap->update($data);

        if ($request->has('blogIds')) {
            $societyMap->blogs()->sync($request->input('blogIds', []));
        }

        $societyMap->load(['location', 'city', 'blogs']);

        return new SocietyMapResource($societyMap);
    }

    public function destroy(SocietyMap $societyMap): JsonResponse
    {
        if ($societyMap->image) {
            $this->images->delete($societyMap->image);
        }

        if ($societyMap->document) {
            $this->images->delete($societyMap->document);
        }

        $societyMap->delete();

        return response()->json(['message' => 'Map deleted.']);
    }

    /**
     * Turn the request into columns.
     */
    private function prepare(SocietyMapRequest $request, ?SocietyMap $existing = null): array
    {
        $input = $request->validated();

        $data = [
            'title' => $input['title'],
            'summary' => $input['summary'] ?? '',
            /* The write-up comes from a rich-text editor, so it is HTML
               from a browser and is sanitised before it is stored. */
            'description' => Purifier::clean($input['description'] ?? '', 'blog'),
            'status' => $input['status'],
            'featured' => (bool) ($input['featured'] ?? false),
            'sort_order' => (int) ($input['sortOrder'] ?? 0),
            'meta_title' => $input['metaTitle'] ?? null,
            'meta_description' => $input['metaDescription'] ?? null,
        ];

        if (isset($input['locationId'])) {
            $location = Location::find($input['locationId']);

            $data['location_id'] = $input['locationId'];
            /* Resolved on save so a listing can filter by city without
               walking up the tree for every row. */
            $data['city_id'] = $location
                ? SocietyMap::resolveCity($location)?->id
                : null;
        }

        /* The slug is the published URL. It is set once, so renaming a
           map for clarity does not break links already shared. */
        if ($existing === null) {
            $data['slug'] = SocietyMap::uniqueSlug($input['title']);
        }

        if ($data['status'] === BlogStatus::Published->value && ! $existing?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
