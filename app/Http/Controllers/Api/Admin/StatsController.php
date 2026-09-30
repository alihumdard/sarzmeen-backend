<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Blog;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'totalProperties' => Property::count(),
                'totalProjects' => Project::count(),
                'totalBlogs' => Blog::count(),
                'totalInquiries' => Inquiry::count(),
                'totalUsers' => User::count(),
                'totalAgencies' => Agency::count(),
                'totalAgents' => Agent::count(),
                'pendingAccounts' => User::where('status', 'pending')->count(),
                'approvedAccounts' => User::where('status', 'approved')->count(),
                'rejectedAccounts' => User::where('status', 'rejected')->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }
}
