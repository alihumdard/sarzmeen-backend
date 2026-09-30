<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class TestimonialController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $testimonials = Cache::remember('public_testimonials', 300, function () {
            return Testimonial::published()
                ->orderByDesc('featured')
                ->latest()
                ->get();
        });

        return TestimonialResource::collection($testimonials);
    }
}
