<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InquiryStatus;
use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\Blog;
use App\Models\PageViewLog;
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
                    ->toBase()
                    ->pluck('count', 'status')
                    ->all(),

                'propertiesByPurpose' => Property::where('status', PropertyStatus::Published)
                    ->select('purpose', DB::raw('count(*) as count'))
                    ->groupBy('purpose')
                    ->toBase()
                    ->pluck('count', 'purpose')
                    ->all(),

                'inquiriesByStatus' => Inquiry::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->toBase()
                    ->pluck('count', 'status')
                    ->all(),

                'newListingsLast30Days' => Property::where('created_at', '>=', $thirtyDaysAgo)->count(),
                'newInquiriesLast30Days' => Inquiry::where('created_at', '>=', $thirtyDaysAgo)->count(),
                'newUsersLast30Days' => User::where('created_at', '>=', $thirtyDaysAgo)->count(),
                'totalViews' => Property::sum('views'),
                'totalViewsLast30Days' => PropertyViewLog::where('date', '>=', $thirtyDaysAgo)->sum('count'),

                /*
                 * What people came to look at. Only properties were ever
                 * counted, so there was no telling whether the projects
                 * were being read or the blog opened at all.
                 *
                 * Properties come from their own table, which predates
                 * the rest; both are counted over the same window so the
                 * figures can sit beside each other.
                 */
                'viewsByType' => static::viewsByType($thirtyDaysAgo),
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Views per kind of page over the window, largest first.
     *
     * A kind nobody looked at is left out rather than shown as nought: an
     * empty row says less than its absence, and the list is read as "what
     * people are looking at".
     */
    private static function viewsByType(\Carbon\CarbonInterface $since): array
    {
        $counts = PageViewLog::where('date', '>=', $since)
            ->select('viewable_type', DB::raw('sum(count) as total'))
            ->groupBy('viewable_type')
            ->toBase()
            ->pluck('total', 'viewable_type')
            ->map(fn ($n) => (int) $n)
            ->all();

        $counts['property'] = (int) PropertyViewLog::where('date', '>=', $since)->sum('count');

        $labels = [
            'property' => 'Properties',
            'project' => 'Projects',
            'blog' => 'Blogs',
            'agent' => 'Agents',
            'agency' => 'Agencies',
            'service' => 'Services',
            'video' => 'Videos',
        ];

        return collect($counts)
            ->filter(fn (int $n) => $n > 0)
            ->sortDesc()
            ->map(fn (int $n, string $kind) => [
                'kind' => $kind,
                'label' => $labels[$kind] ?? ucfirst($kind),
                'count' => $n,
            ])
            ->values()
            ->all();
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
                ->toBase()
                ->get()
                ->map(fn ($row) => ['city' => $row->city, 'count' => (int) $row->count])
                ->all();
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
                ->toBase()
                ->get()
                ->map(fn ($row) => ['type' => $row->type, 'count' => (int) $row->count])
                ->all();
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

        // Selected raw: the model casts `date` to a Carbon instance, which
        // would key the lookup below as a full timestamp rather than Y-m-d.
        $logged = PropertyViewLog::where('date', '>=', now()->subDays($days)->startOfDay())
            ->select(DB::raw('date::text as day'), DB::raw('sum(count) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');

        /*
         * Only days with views have rows, so a new site returns a single
         * point — and one point cannot draw a line. Filling the gaps with
         * zero gives the chart a continuous series over the window asked
         * for, which is also the honest shape: no views means zero, not a
         * missing day.
         */
        $data = collect(range($days - 1, 0))->map(function (int $ago) use ($logged) {
            $date = now()->subDays($ago)->toDateString();

            return [
                'date' => $date,
                'count' => (int) ($logged[$date] ?? 0),
            ];
        });

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

    /**
     * The most-read posts over the window.
     *
     * Counted from the view log rather than a column on the post: blogs
     * have no running total of their own, and the log is what the
     * dashboard's other view figures are read from, so the two agree.
     */
    public function topBlogs(Request $request): JsonResponse
    {
        $days = min($request->integer('days', 30), 365);
        $limit = min($request->integer('limit', 5), 25);
        $since = now()->subDays($days)->startOfDay();

        $counts = PageViewLog::where('viewable_type', 'blog')
            ->where('date', '>=', $since)
            ->select('viewable_id', DB::raw('sum(count) as total'))
            ->groupBy('viewable_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->toBase()
            ->pluck('total', 'viewable_id');

        if ($counts->isEmpty()) {
            return response()->json(['data' => []]);
        }

        /* One query for the posts, then put them back in view order —
           whereIn returns them by id. */
        $blogs = Blog::whereIn('id', $counts->keys())
            ->with('category')
            ->get()
            ->keyBy('id');

        $data = $counts
            ->map(function ($total, $id) use ($blogs) {
                $blog = $blogs->get($id);

                if (! $blog) {
                    return null;
                }

                return [
                    'id' => (string) $blog->id,
                    'slug' => $blog->slug,
                    'title' => $blog->title,
                    'category' => $blog->category?->name,
                    'publishedAt' => $blog->published_at?->toDateString(),
                    'views' => (int) $total,
                ];
            })
            /* A post deleted since it was read leaves a row with nothing
               to name; drop it rather than show a blank line. */
            ->filter()
            ->values();

        return response()->json(['data' => $data]);
    }

    /** Daily blog views over the window, with the quiet days filled in. */
    public function blogViewsTrend(Request $request): JsonResponse
    {
        $days = min($request->integer('days', 30), 90);

        $logged = PageViewLog::where('viewable_type', 'blog')
            ->where('date', '>=', now()->subDays($days)->startOfDay())
            ->select(DB::raw('date::text as day'), DB::raw('sum(count) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');

        /* Days with no views have no rows, and a chart drawn from those
           alone would skip them rather than show the dip. */
        $data = collect(range($days - 1, 0))->map(function (int $ago) use ($logged) {
            $date = now()->subDays($ago)->toDateString();

            return [
                'date' => $date,
                'count' => (int) ($logged[$date] ?? 0),
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => (int) $logged->sum(),
        ]);
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
            })->all();
        });

        return response()->json(['data' => $data]);
    }
}
