<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
use App\Notifications\InquiryStatusChangedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InquiryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Inquiry::with(['property.images', 'property.propertyType', 'project'])
            ->latest();

        if ($request->filled('status')) {
            $status = InquiryStatus::tryFrom($request->string('status')->toString());
            if ($status) {
                $query->byStatus($status);
            }
        }

        if ($request->filled('source')) {
            $query->where('source', $request->string('source')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('message', 'ilike', "%{$search}%");
            });
        }

        return InquiryResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    /** Status counts for the dashboard cards, unaffected by the active filters. */
    public function stats(): JsonResponse
    {
        $counts = Inquiry::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'total' => (int) $counts->sum(),
                'new' => (int) ($counts[InquiryStatus::New->value] ?? 0),
                'pending' => (int) ($counts[InquiryStatus::Pending->value] ?? 0),
                'contacted' => (int) ($counts[InquiryStatus::Contacted->value] ?? 0),
                'closed' => (int) ($counts[InquiryStatus::Closed->value] ?? 0),
            ],
        ]);
    }

    public function show(Inquiry $inquiry): InquiryResource
    {
        $inquiry->load(['property.images', 'property.propertyType', 'project', 'user']);

        return new InquiryResource($inquiry);
    }

    public function updateStatus(Request $request, Inquiry $inquiry): InquiryResource
    {
        $request->validate([
            'status' => ['required', 'in:new,pending,contacted,closed'],
        ]);

        $oldStatus = $inquiry->status->value;
        $newStatus = $request->string('status')->toString();

        $inquiry->update(['status' => $newStatus]);

        if ($oldStatus !== $newStatus && $inquiry->user) {
            $inquiry->user->notify(
                new InquiryStatusChangedNotification($inquiry, $oldStatus)
            );
        }

        $inquiry->load(['property.images', 'property.propertyType', 'project']);

        return new InquiryResource($inquiry);
    }

    public function destroy(Inquiry $inquiry): JsonResponse
    {
        $inquiry->delete();

        return response()->json(null, 204);
    }
}
