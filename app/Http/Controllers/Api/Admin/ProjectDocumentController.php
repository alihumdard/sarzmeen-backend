<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Brochures, NOCs, payment schedules and the like.
 *
 * Documents are uploaded one at a time with a title, rather than riding
 * along with the project form: each needs naming, and a brochure added
 * months later should not mean resubmitting the whole project.
 */
class ProjectDocumentController extends Controller
{
    /** The kinds offered in the admin picker. */
    private const TYPES = ['brochure', 'floor_plan', 'payment_plan', 'noc', 'legal', 'other'];

    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function store(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'in:' . implode(',', self::TYPES)],
            'isPublic' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:10240'],
        ], [
            /*
             * A file over PHP's own upload ceiling never reaches Laravel, so
             * it surfaces as `uploaded` rather than `max` -- both mean the
             * same thing to the person uploading.
             */
            'file.uploaded' => 'The document must be 10MB or smaller.',
            'file.max' => 'The document must be 10MB or smaller.',
            'file.mimes' => 'Upload a PDF, Word or Excel file, or an image.',
        ]);

        // No width: a PDF must be stored byte for byte, not run through the
        // image resizer.
        $path = $this->imageService->upload(
            $request->file('file'),
            "projects/{$project->id}/documents",
            null,
        );

        $document = $project->documents()->create([
            'title' => $data['title'],
            'type' => $data['type'] ?? 'other',
            'path' => $path,
            'is_public' => $request->boolean('isPublic', true),
            'sort_order' => ($project->documents()->max('sort_order') ?? -1) + 1,
        ]);

        return response()->json(['data' => $this->present($document)], 201);
    }

    public function destroy(Project $project, ProjectDocument $document): JsonResponse
    {
        // Route binding resolves the document on its own, so it could belong
        // to another project; 404 rather than delete someone else's file.
        if ($document->project_id !== $project->id) {
            abort(404);
        }

        $this->imageService->delete($document->path);
        $document->delete();

        return response()->json(null, 204);
    }

    /** The same shape the project resource sends, so the form can just append. */
    private function present(ProjectDocument $document): array
    {
        return [
            'id' => (string) $document->id,
            'title' => $document->title,
            'type' => $document->type,
            'url' => $this->imageService->url($document->path),
            'isPublic' => $document->is_public,
        ];
    }
}
