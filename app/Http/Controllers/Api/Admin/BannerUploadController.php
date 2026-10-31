<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Uploads a homepage banner or section image.
 *
 * The Homepage CMS used to take a path typed by hand, which meant a file had
 * to be placed in /public before it could be referenced. This stores the
 * upload and hands back the URL to save in the setting.
 */
class BannerUploadController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        /*
         * A banner spans the page, so it has a size floor. An image dropped
         * into a blog post does not — a small diagram or logo is a perfectly
         * good illustration, and holding it to the banner rule meant the
         * editor simply refused it.
         */
        $inline = $request->input('context') === 'inline';

        $rules = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

        if (! $inline) {
            $rules[] = 'dimensions:min_width=400,min_height=400';
        }

        $request->validate([
            'context' => ['nullable', 'in:banner,inline'],
            'image' => $rules,
        ], [
            'image.max' => 'The image must be 2MB or smaller.',
            'image.dimensions' => 'The banner must be at least 400x400 pixels.',
        ]);

        // Wide enough for a full-bleed banner without storing a camera original.
        $path = $this->imageService->upload(
            $request->file('image'),
            $inline ? 'blog-content' : 'banners',
            1920,
            86,
        );

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => $this->imageService->url($path),
            ],
        ], 201);
    }
}
