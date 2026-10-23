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
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=400,min_height=400',
            ],
        ], [
            'image.max' => 'The banner must be 2MB or smaller.',
            'image.dimensions' => 'The banner must be at least 400x400 pixels.',
        ]);

        // Wide enough for a full-bleed banner without storing a camera original.
        $path = $this->imageService->upload(
            $request->file('image'),
            'banners',
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
