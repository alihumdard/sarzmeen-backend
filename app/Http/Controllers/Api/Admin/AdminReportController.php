<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminReportResource;
use App\Models\AdminReport;
use App\Notifications\ReportAnsweredNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The admin's side of the reports agents and agencies send in.
 */
class AdminReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(AdminReport::STATUSES)],
            'type' => ['nullable', Rule::in(AdminReport::TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $reports = AdminReport::with(['user', 'repliedBy'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate($filters['per_page'] ?? 20);

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

    /** Counts for the badge and the filter tabs. */
    public function stats(): JsonResponse
    {
        $counts = AdminReport::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'new' => (int) ($counts['new'] ?? 0),
                'inReview' => (int) ($counts['in_review'] ?? 0),
                'resolved' => (int) ($counts['resolved'] ?? 0),
                'declined' => (int) ($counts['declined'] ?? 0),
            ],
        ]);
    }

    public function show(Request $request, AdminReport $report): JsonResponse
    {
        $report->load(['user', 'repliedBy']);

        return response()->json(['data' => (new AdminReportResource($report))->resolve($request)]);
    }

    /**
     * Change the status, and optionally answer at the same time.
     *
     * The reply is kept on the report rather than sent as a message, so the
     * sender sees the answer next to what they asked.
     */
    public function update(Request $request, AdminReport $report): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(AdminReport::STATUSES)],
            'adminReply' => ['nullable', 'string', 'max:4000'],
        ]);

        $reply = trim((string) ($data['adminReply'] ?? ''));

        $report->status = $data['status'];

        if ($reply !== '') {
            $report->admin_reply = $reply;
            $report->replied_by = $request->user()->id;
            $report->replied_at = now();
        }

        $report->save();

        /*
         * Tell the sender only when something reached them: a status moved
         * on quietly in the background is not worth a notification.
         */
        if ($reply !== '' || in_array($data['status'], ['resolved', 'declined'], true)) {
            $report->user?->notify(new ReportAnsweredNotification($report));
        }

        $report->load(['user', 'repliedBy']);

        return response()->json(['data' => (new AdminReportResource($report))->resolve($request)]);
    }

    public function destroy(AdminReport $report): JsonResponse
    {
        $report->delete();

        return response()->json(null, 204);
    }
}
