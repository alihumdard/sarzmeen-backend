<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyViewLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyAnalyticsController extends Controller
{
    public function __invoke(Request $request, Property $property): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            ($property->owner_type === \App\Models\User::class && $property->owner_id === $user->id)
            || $user->hasRole('admin'),
            403,
        );

        $days = $request->integer('days', 30);
        $days = min($days, 90);

        $viewsTrend = PropertyViewLog::where('property_id', $property->id)
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get()
            ->map(fn ($log) => [
                'date' => $log->date->toDateString(),
                'views' => $log->count,
            ]);

        return response()->json([
            'data' => [
                'totalViews' => $property->views,
                'viewsLast30Days' => PropertyViewLog::where('property_id', $property->id)
                    ->where('date', '>=', now()->subDays(30))
                    ->sum('count'),
                'trend' => $viewsTrend,
            ],
        ]);
    }
}
