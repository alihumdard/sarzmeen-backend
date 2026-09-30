<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Blog;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->roles->first()?->name;

        $stats = match ($role) {
            'admin' => $this->adminStats(),
            'agency' => $this->agencyStats($user),
            'agent' => $this->agentStats($user),
            default => $this->userStats($user),
        };

        return response()->json(['data' => $stats]);
    }

    private function adminStats(): array
    {
        return Cache::remember('admin_stats', 300, function () {
            return [
                'totalProperties' => Property::count(),
                'totalProjects' => Project::count(),
                'totalBlogs' => Blog::count(),
                'totalInquiries' => Inquiry::count(),
                'totalUsers' => User::count(),
                'totalAgencies' => Agency::count(),
                'totalAgents' => Agent::count(),
                'pendingAccounts' => User::where('status', 'pending')->count(),
                'pendingProperties' => Property::where('status', 'pending')->count(),
            ];
        });
    }

    private function agencyStats(User $user): array
    {
        $agency = $user->agency;

        if (! $agency) {
            return $this->emptyStats();
        }

        return [
            'totalAgents' => $agency->agents()->count(),
            'totalProperties' => 0,
            'totalInquiries' => 0,
            'propertiesForSale' => 0,
            'propertiesForRent' => 0,
        ];
    }

    private function agentStats(User $user): array
    {
        return [
            'totalProperties' => 0,
            'totalInquiries' => 0,
            'propertiesForSale' => 0,
            'propertiesForRent' => 0,
        ];
    }

    private function userStats(User $user): array
    {
        return [
            'totalProperties' => 0,
            'totalInquiries' => 0,
            'savedProperties' => 0,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'totalAgents' => 0,
            'totalProperties' => 0,
            'totalInquiries' => 0,
            'propertiesForSale' => 0,
            'propertiesForRent' => 0,
        ];
    }
}
