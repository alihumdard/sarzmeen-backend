<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProjectRequest;
use App\Http\Resources\ProjectDetailResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::with(['images', 'category', 'location.parent.parent'])
            ->latest();

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->input('category')));
        }

        return ProjectResource::collection($query->paginate(15));
    }

    public function show(Project $project): ProjectDetailResource
    {
        $project->load([
            'images', 'category', 'location.parent.parent',
            'amenities', 'paymentPlans', 'highlights', 'plotSizes', 'units', 'developerProfile', 'faqs', 'documents', 'floorPlans',
        ]);

        return new ProjectDetailResource($project);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = DB::transaction(function () use ($request) {
            $data = $request->validated();

            $project = Project::create([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'project_category_id' => $data['projectCategoryId'],
                'project_type' => $data['projectType'],
                'project_format' => $data['projectFormat'],
                'payment_type' => $data['paymentType'],
                'towers_count' => $data['towersCount'] ?? null,
                'total_floors' => $data['totalFloors'] ?? null,
                'basement_floors' => $data['basementFloors'] ?? null,
                'parking_spaces' => $data['parkingSpaces'] ?? null,
                'expected_completion' => $data['expectedCompletion'] ?? null,
                'possession_timeline' => $data['possessionTimeline'] ?? null,
                'developer' => $data['developer'],
                'developer_id' => $data['developerId'] ?? null,
                'location_id' => $data['locationId'],
                'full_location' => $data['fullLocation'] ?? '',
                'status' => $data['status'],
                'price_from' => $data['priceFrom'] ?? '',
                'total_area' => $data['totalArea'] ?? '',
                'total_units' => $data['totalUnits'] ?? null,
                'description' => $data['description'] ?? '',
                'verified' => $data['verified'] ?? false,
                'featured' => $data['featured'] ?? false,
                'meta_title' => $data['metaTitle'] ?? null,
                'meta_description' => $data['metaDescription'] ?? null,
                'focus_keyword' => $data['focusKeyword'] ?? null,
                'canonical_url' => $data['canonicalUrl'] ?? null,
                'og_title' => $data['ogTitle'] ?? null,
                'og_description' => $data['ogDescription'] ?? null,
                'og_image' => $data['ogImage'] ?? null,
                'payment_terms' => $data['paymentTerms'] ?? [],
            ]);

            $this->syncRelations($project, $data);

            return $project;
        });

        $project->load(['images', 'category', 'location.parent.parent', 'amenities', 'paymentPlans', 'highlights', 'plotSizes', 'units', 'developerProfile', 'faqs', 'documents', 'floorPlans']);

        return (new ProjectDetailResource($project))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProjectRequest $request, Project $project): ProjectDetailResource
    {
        DB::transaction(function () use ($request, $project) {
            $data = $request->validated();

            $project->update([
                'slug' => $data['slug'],
                'name' => $data['name'],
                'project_category_id' => $data['projectCategoryId'],
                'project_type' => $data['projectType'],
                'project_format' => $data['projectFormat'],
                'payment_type' => $data['paymentType'],
                'towers_count' => $data['towersCount'] ?? null,
                'total_floors' => $data['totalFloors'] ?? null,
                'basement_floors' => $data['basementFloors'] ?? null,
                'parking_spaces' => $data['parkingSpaces'] ?? null,
                'expected_completion' => $data['expectedCompletion'] ?? null,
                'possession_timeline' => $data['possessionTimeline'] ?? null,
                'developer' => $data['developer'],
                'developer_id' => $data['developerId'] ?? null,
                'location_id' => $data['locationId'],
                'full_location' => $data['fullLocation'] ?? '',
                'status' => $data['status'],
                'price_from' => $data['priceFrom'] ?? '',
                'total_area' => $data['totalArea'] ?? '',
                'total_units' => $data['totalUnits'] ?? null,
                'description' => $data['description'] ?? '',
                'verified' => $data['verified'] ?? false,
                'featured' => $data['featured'] ?? false,
                'meta_title' => $data['metaTitle'] ?? null,
                'meta_description' => $data['metaDescription'] ?? null,
                'focus_keyword' => $data['focusKeyword'] ?? null,
                'canonical_url' => $data['canonicalUrl'] ?? null,
                'og_title' => $data['ogTitle'] ?? null,
                'og_description' => $data['ogDescription'] ?? null,
                'og_image' => $data['ogImage'] ?? null,
                'payment_terms' => $data['paymentTerms'] ?? [],
            ]);

            $this->syncRelations($project, $data);
        });

        $project->load(['images', 'category', 'location.parent.parent', 'amenities', 'paymentPlans', 'highlights', 'plotSizes', 'units', 'developerProfile', 'faqs', 'documents', 'floorPlans']);

        return new ProjectDetailResource($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        foreach ($project->images as $image) {
            $this->imageService->delete($image->path);
        }

        $project->delete();

        return response()->json(null, 204);
    }

    private function syncRelations(Project $project, array $data): void
    {
        if (array_key_exists('amenities', $data)) {
            $project->amenities()->delete();
            if (! empty($data['amenities'])) {
                $project->amenities()->createMany(
                    array_map(fn (string $name) => ['name' => $name], $data['amenities'])
                );
            }
        }

        if (array_key_exists('highlights', $data)) {
            $project->highlights()->delete();
            if (! empty($data['highlights'])) {
                $project->highlights()->createMany(
                    array_map(fn (string $text) => ['text' => $text], $data['highlights'])
                );
            }
        }

        if (array_key_exists('plotSizes', $data)) {
            $project->plotSizes()->delete();
            if (! empty($data['plotSizes'])) {
                $project->plotSizes()->createMany(
                    array_map(fn (string $size) => ['size' => $size], $data['plotSizes'])
                );
            }
        }

        if (array_key_exists('paymentPlan', $data)) {
            $project->paymentPlans()->delete();
            if (! empty($data['paymentPlan'])) {
                foreach ($data['paymentPlan'] as $i => $plan) {
                    $project->paymentPlans()->create([
                        'label' => $plan['label'],
                        'value' => $plan['value'],
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        if (array_key_exists('faqs', $data)) {
            $project->faqs()->delete();
            if (! empty($data['faqs'])) {
                foreach ($data['faqs'] as $i => $faq) {
                    $project->faqs()->create([
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        if (array_key_exists('floorPlans', $data)) {
            $project->floorPlans()->delete();
            if (! empty($data['floorPlans'])) {
                foreach ($data['floorPlans'] as $i => $plan) {
                    $project->floorPlans()->create([
                        'name' => $plan['name'],
                        'floor_number' => $plan['floorNumber'] ?? null,
                        'tower' => $plan['tower'] ?? null,
                        'description' => $plan['description'] ?? '',
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        if (array_key_exists('units', $data)) {
            $project->units()->delete();
            if (! empty($data['units'])) {
                foreach ($data['units'] as $i => $unit) {
                    $project->units()->create([
                        'unit_type' => $unit['unitType'],
                        'unit_name' => $unit['unitName'] ?? null,
                        'category' => $unit['category'] ?? 'residential',
                        'area' => $unit['area'],
                        'min_area' => $unit['minArea'] ?? $unit['area'],
                        'max_area' => $unit['maxArea'] ?? $unit['area'],
                        'area_unit' => $unit['areaUnit'] ?? 'sqft',
                        'rate_per_sqft' => $unit['ratePerSqft'],
                        'starting_price' => $unit['area'] * $unit['ratePerSqft'],
                        'max_price' => $unit['maxPrice'] ?? null,
                        'availability' => $unit['availability'] ?? 'available',
                        'details' => $unit['details'] ?? [],
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        if (array_key_exists('towers', $data)) {
            /*
             * Replaced wholesale, like units: towers carry no history of their
             * own and cascade to their floors, so rebuilding is simpler than
             * diffing and cannot leave orphans.
             */
            $project->towers()->delete();

            foreach ($data['towers'] ?? [] as $i => $tower) {
                if (empty($tower['name'])) {
                    continue;
                }

                $created = $project->towers()->create([
                    'name' => $tower['name'],
                    'floors_count' => $tower['floorsCount'] ?? null,
                    'units_count' => $tower['unitsCount'] ?? null,
                    'description' => $tower['description'] ?? null,
                    'sort_order' => $i,
                ]);

                foreach ($tower['floors'] ?? [] as $j => $floor) {
                    if (! isset($floor['floorNumber'])) {
                        continue;
                    }

                    $created->floors()->create([
                        'name' => $floor['name'] ?? null,
                        'floor_number' => (int) $floor['floorNumber'],
                        'description' => $floor['description'] ?? null,
                        'sort_order' => $j,
                    ]);
                }
            }
        }

        if (! empty($data['images'])) {
            $maxSort = $project->images()->max('sort_order') ?? -1;
            $isFirst = $project->images()->count() === 0;
            foreach ($data['images'] as $index => $image) {
                $path = $this->imageService->upload($image, "projects/{$project->id}");
                $project->images()->create([
                    'path' => $path,
                    'is_cover' => $isFirst && $index === 0,
                    'sort_order' => $maxSort + $index + 1,
                ]);
            }
        }
    }
}
