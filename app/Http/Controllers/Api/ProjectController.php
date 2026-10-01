<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectDetailResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::with(['images', 'category', 'location.parent'])
            ->latest();

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')->toString()));
        }

        if ($request->filled('city')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('slug', $request->string('city')->toString())
                    ->orWhereHas('parent', fn ($p) => $p->where('slug', $request->string('city')->toString()));
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereRaw(
                "to_tsvector('english', coalesce(name, '') || ' ' || coalesce(description, '') || ' ' || coalesce(full_location, '')) @@ plainto_tsquery('english', ?)",
                [$search],
            );
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        return ProjectResource::collection($query->paginate($request->integer('per_page', 12)));
    }

    public function show(string $slug): ProjectDetailResource
    {
        $project = Project::where('slug', $slug)
            ->with([
                'images',
                'category',
                'location.parent',
                'amenities',
                'paymentPlans',
                'highlights',
                'plotSizes',
            ])
            ->firstOrFail();

        return new ProjectDetailResource($project);
    }
}
