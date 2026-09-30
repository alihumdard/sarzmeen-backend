<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class TestimonialController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $testimonials = Testimonial::orderByDesc('featured')
            ->latest()
            ->get();

        return TestimonialResource::collection($testimonials);
    }

    public function store(TestimonialRequest $request): JsonResponse
    {
        $testimonial = Testimonial::create($request->validated());

        $this->clearCache();

        return (new TestimonialResource($testimonial))
            ->response()
            ->setStatusCode(201);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        $testimonial->update($request->validated());

        $this->clearCache();

        return new TestimonialResource($testimonial);
    }

    public function destroy(Testimonial $testimonial): JsonResponse
    {
        $testimonial->delete();

        $this->clearCache();

        return response()->json(null, 204);
    }

    private function clearCache(): void
    {
        Cache::forget('public_testimonials');
    }
}
