<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Activity::with('causer')->latest();

        if ($request->filled('log')) {
            $query->whereIn('log_name', explode(',', $request->string('log')->toString()));
        }

        if ($request->filled('event')) {
            $query->whereIn('event', explode(',', $request->string('event')->toString()));
        }

        if ($request->filled('causer')) {
            $query->where('causer_id', $request->integer('causer'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('causer', fn ($q) => $q->where('name', 'ilike', "%{$search}%"));
        }

        return ActivityResource::collection(
            $query->paginate(min($request->integer('per_page', 25), 100)),
        );
    }

    /** Distinct values the UI offers as filters, taken from what exists. */
    public function filters(): JsonResponse
    {
        return response()->json(['data' => [
            'logs' => Activity::distinct()->orderBy('log_name')->pluck('log_name')->filter()->values(),
            'events' => Activity::distinct()->orderBy('event')->pluck('event')->filter()->values(),
        ]]);
    }
}
