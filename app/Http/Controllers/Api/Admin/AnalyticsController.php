<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InquiryStatus;
use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\PropertyViewLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function overview(): JsonResponse
    {
        $data = Cache::remember('admin_analytics_overview', 300, function () {
            $now = now();
            $thirtyDaysAgo = $now->copy()->subDays(30);

            return [
                'propertiesByStatus' => Property::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),

                'propertiesByPurpose' => Property::where('status', PropertyStatus::Published)
                    ->select('purpose', DB::raw('count(*) as count'))
                    ->groupBy('purpose')
                    ->pluck('count', 'purpose'),

                'inquiriesByStatus' => Inquiry::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),

                'newListingsLast30Days' => Property::where('created_at', '>=', $thirtyDaysAgo)->count(),
                'newInquiriesLast30Days' => Inquiry::where('created_at', '>=', $thirtyDaysAgo)->count(),
                'newUsersLast30Days' => User::where('created_at', '>=', $thirtyDaysAgo)->count(),
                'totalViews' => Property::sum('views'),
                'totalViewsLast30Days' => PropertyViewLog::where('date', '>=', $thirtyDaysAgo)->sum('count'),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function propertiesByCity(): JsonResponse
    {
        $data = Cache::remember('analytics_properties_by_city', 600, function () {
            return Property::where('properties.status', PropertyStatus::Published)
                ->join('locations', 'properties.location_id', '=', 'locations.id')
                ->leftJoin('locations as parent_locations', 'locations.parent_id', '=', 'parent_locations.id')
                ->select(
                    DB::raw("coalesce(parent_locations.name, locations.name) as city"),
                    DB::raw('count(*) as count'),
                )
                ->groupBy('city')
                ->orderByDesc('count')
                ->limit(15)
                ->get();
        });

        return response()->json(['data' => $data]);
    }

    public function propertiesByType(): JsonResponse
    {
        $data = Cache::remember('analytics_properties_by_type', 600, function () {
            return Property::where('properties.status', PropertyStatus::Published)
                ->join('property_types', 'properties.property_type_id', '=', 'property_types.id')
                ->select('property_types.name as type', DB::raw('count(*) as count'))
                ->groupBy('property_types.name')
                ->orderByDesc('count')
                ->get();
        });

        return response()->json(['data' => $data]);
    }

    public function inquiriesTrend(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $days = min($days, 90);

        $data = Inquiry::where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw("date(created_at) as date"), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function registrationsTrend(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $days = min($days, 90);

        $data = User::where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw("date(created_at) as date"), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function viewsTrend(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $days = min($days, 90);

        $data = PropertyViewLog::where('date', '>=', now()->subDays($days))
            ->select('date', DB::raw('sum(count) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function topProperties(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 10);

        $data = Property::where('status', PropertyStatus::Published)
            ->with(['location', 'propertyType'])
            ->orderByDesc('views')
            ->limit(min($limit, 25))
            ->get()
            ->map(fn (Property $p) => [
                'id' => (string) $p->id,
                'slug' => $p->slug,
                'title' => $p->title,
                'location' => $p->full_location,
                'type' => $p->propertyType->name,
                'purpose' => $p->purpose->value,
                'price' => (float) $p->price,
                'views' => $p->views,
            ]);

        return response()->json(['data' => $data]);
    }

    public function priceDistribution(): JsonResponse
    {
        $data = Cache::remember('analytics_price_distribution', 600, function () {
            $ranges = [
                ['label' => '< 10M', 'min' => 0, 'max' => 10_000_000],
                ['label' => '10M - 25M', 'min' => 10_000_000, 'max' => 25_000_000],
                ['label' => '25M - 50M', 'min' => 25_000_000, 'max' => 50_000_000],
                ['label' => '50M - 100M', 'min' => 50_000_000, 'max' => 100_000_000],
                ['label' => '100M - 250M', 'min' => 100_000_000, 'max' => 250_000_000],
                ['label' => '250M+', 'min' => 250_000_000, 'max' => null],
            ];

            return collect($ranges)->map(function (array $range) {
                $query = Property::where('status', PropertyStatus::Published)
                    ->where('price', '>=', $range['min']);

                if ($range['max'] !== null) {
                    $query->where('price', '<', $range['max']);
                }

                return [
                    'label' => $range['label'],
                    'count' => $query->count(),
                ];
            });
        });

        return response()->json(['data' => $data]);
    }
}
