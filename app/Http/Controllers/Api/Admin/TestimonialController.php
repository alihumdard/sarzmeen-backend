<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}
    public function index(): AnonymousResourceCollection
    {
        $testimonials = Testimonial::orderByDesc('featured')
            ->latest()
            ->get();

        return TestimonialResource::collection($testimonials);
    }

    public function store(TestimonialRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatarFile')) {
            $data['avatar'] = $this->imageService->upload(
                $request->file('avatarFile'),
                'testimonials',
                300,
            );
        }

        $testimonial = Testimonial::create($data);

        $this->clearCache();

        return (new TestimonialResource($testimonial))
            ->response()
            ->setStatusCode(201);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        $data = $request->validated();

        if ($request->hasFile('avatarFile')) {
            if ($testimonial->avatar) {
                $this->imageService->delete($testimonial->avatar);
            }
            $data['avatar'] = $this->imageService->upload(
                $request->file('avatarFile'),
                'testimonials',
                300,
            );
        }

        $testimonial->update($data);

        $this->clearCache();

        return new TestimonialResource($testimonial);
    }

    public function destroy(Testimonial $testimonial): JsonResponse
    {
        if ($testimonial->avatar) {
            $this->imageService->delete($testimonial->avatar);
        }

        $testimonial->delete();

        $this->clearCache();

        return response()->json(null, 204);
    }

    private function clearCache(): void
    {
        Cache::forget('public_testimonials');
    }
}
