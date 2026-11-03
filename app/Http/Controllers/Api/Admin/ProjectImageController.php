<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;

/**
 * Removing and re-covering a project's stored images.
 *
 * Uploading goes through the project form itself, which posts the whole
 * project; these two act on one image that is already saved, which the form
 * cannot express by resubmitting the project.
 */
class ProjectImageController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function destroy(Project $project, ProjectImage $image): JsonResponse
    {
        // Route binding resolves the image on its own, so it could belong to
        // another project; 404 rather than delete someone else's photo.
        if ($image->project_id !== $project->id) {
            abort(404);
        }

        $this->imageService->delete($image->path);

        $wasCover = $image->is_cover;
        $image->delete();

        // The project would otherwise be left with no cover at all.
        if ($wasCover) {
            $next = $project->images()->orderBy('sort_order')->first();
            $next?->update(['is_cover' => true]);
        }

        return response()->json(null, 204);
    }

    public function setCover(Project $project, ProjectImage $image): JsonResponse
    {
        if ($image->project_id !== $project->id) {
            abort(404);
        }

        $project->images()->update(['is_cover' => false]);
        $image->update(['is_cover' => true]);

        return response()->json(null, 204);
    }
}
