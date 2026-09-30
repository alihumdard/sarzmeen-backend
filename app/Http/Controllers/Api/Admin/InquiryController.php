<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
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

        return InquiryResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
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

        $inquiry->update(['status' => $request->input('status')]);

        return new InquiryResource($inquiry);
    }

    public function destroy(Inquiry $inquiry): JsonResponse
    {
        $inquiry->delete();

        return response()->json(null, 204);
    }
}
