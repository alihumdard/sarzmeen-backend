<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = $request->user();

        // Upload first: deleting first meant a failed upload lost the
        // picture and left the record pointing at a missing file.
        $previous = $user->avatar;

        $path = $this->imageService->upload(
            $request->file('avatar'),
            'avatars',
            400,
            90,
        );

        $user->update(['avatar' => $path]);

        if ($previous && $previous !== $path) {
            $this->imageService->delete($previous);
        }

        return response()->json([
            'data' => [
                'avatar' => $this->imageService->url($path),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            $this->imageService->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return response()->json(null, 204);
    }
}
