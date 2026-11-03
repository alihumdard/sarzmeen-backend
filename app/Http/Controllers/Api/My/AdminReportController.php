<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminReportResource;
use App\Models\AdminReport;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reports, suggestions and change requests an agent or agency sends in.
 *
 * Every query is scoped to the signed-in user rather than bound from the
 * route, so one account's id can never reach another account's report.
 */
class AdminReportController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $reports = AdminReport::where('user_id', $request->user()->id)
            ->with('repliedBy')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => AdminReportResource::collection($reports->items())->resolve($request),
            'meta' => [
                'currentPage' => $reports->currentPage(),
                'lastPage' => $reports->lastPage(),
                'perPage' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(AdminReport::TYPES)],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            // No width: a PDF has to be stored byte for byte.
            $path = $this->imageService->upload(
                $request->file('attachment'),
                "reports/{$request->user()->id}",
                null,
            );
        }

        $report = AdminReport::create([
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'attachment' => $path,
            'status' => 'new',
        ]);

        return response()->json(
            ['data' => (new AdminReportResource($report))->resolve($request)],
            201,
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $report = AdminReport::where('user_id', $request->user()->id)
            ->with('repliedBy')
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['data' => (new AdminReportResource($report))->resolve($request)]);
    }
}
