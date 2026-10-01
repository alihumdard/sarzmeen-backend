<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\My;

use App\Enums\PropertyPurpose;
use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Blog;
use App\Models\Inquiry;
use App\Models\Property;
use App\Models\PropertyViewLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
                'publishedProperties' => Property::where('status', PropertyStatus::Published)->count(),
                'totalProjects' => Project::count(),
                'totalBlogs' => Blog::count(),
                'totalInquiries' => Inquiry::count(),
                'totalUsers' => User::count(),
                'totalAgencies' => Agency::count(),
                'totalAgents' => Agent::count(),
                'pendingAccounts' => User::where('status', 'pending')->count(),
                'pendingProperties' => Property::where('status', 'pending')->count(),
                'totalViews' => (int) Property::sum('views'),
                'newInquiriesThisMonth' => Inquiry::where('created_at', '>=', now()->startOfMonth())->count(),
                'newUsersThisMonth' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ];
        });
    }

    private function agencyStats(User $user): array
    {
        $agency = $user->agency;

        if (! $agency) {
            return $this->emptyStats();
        }

        $propertyIds = Property::where('agency_id', $agency->id)->pluck('id');

        return [
            'totalAgents' => $agency->agents()->count(),
            'totalProperties' => $propertyIds->count(),
            'publishedProperties' => Property::where('agency_id', $agency->id)
                ->where('status', PropertyStatus::Published)->count(),
            'totalInquiries' => Inquiry::whereIn('property_id', $propertyIds)->count(),
            'totalViews' => (int) Property::where('agency_id', $agency->id)->sum('views'),
            'propertiesForSale' => Property::where('agency_id', $agency->id)
                ->where('purpose', PropertyPurpose::Sale)->count(),
            'propertiesForRent' => Property::where('agency_id', $agency->id)
                ->where('purpose', PropertyPurpose::Rent)->count(),
            'viewsLast30Days' => (int) PropertyViewLog::whereIn('property_id', $propertyIds)
                ->where('date', '>=', now()->subDays(30))->sum('count'),
        ];
    }

    private function agentStats(User $user): array
    {
        $propertyIds = Property::where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->pluck('id');

        return [
            'totalProperties' => $propertyIds->count(),
            'publishedProperties' => Property::where('owner_type', User::class)
                ->where('owner_id', $user->id)
                ->where('status', PropertyStatus::Published)->count(),
            'totalInquiries' => Inquiry::whereIn('property_id', $propertyIds)->count(),
            'totalViews' => (int) Property::where('owner_type', User::class)
                ->where('owner_id', $user->id)->sum('views'),
            'propertiesForSale' => Property::where('owner_type', User::class)
                ->where('owner_id', $user->id)
                ->where('purpose', PropertyPurpose::Sale)->count(),
            'propertiesForRent' => Property::where('owner_type', User::class)
                ->where('owner_id', $user->id)
                ->where('purpose', PropertyPurpose::Rent)->count(),
            'viewsLast30Days' => (int) PropertyViewLog::whereIn('property_id', $propertyIds)
                ->where('date', '>=', now()->subDays(30))->sum('count'),
        ];
    }

    private function userStats(User $user): array
    {
        $propertyIds = Property::where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->pluck('id');

        return [
            'totalProperties' => $propertyIds->count(),
            'publishedProperties' => Property::where('owner_type', User::class)
                ->where('owner_id', $user->id)
                ->where('status', PropertyStatus::Published)->count(),
            'totalInquiries' => Inquiry::whereIn('property_id', $propertyIds)->count(),
            'totalViews' => (int) Property::where('owner_type', User::class)
                ->where('owner_id', $user->id)->sum('views'),
            'unreadNotifications' => $user->unreadNotifications()->count(),
            'unreadMessages' => $user->conversations()
                ->get()
                ->sum(fn ($c) => $c->unreadCountFor($user->id)),
        ];
    }

    private function emptyStats(): array
    {
        return [
            'totalAgents' => 0,
            'totalProperties' => 0,
            'publishedProperties' => 0,
            'totalInquiries' => 0,
            'totalViews' => 0,
            'propertiesForSale' => 0,
            'propertiesForRent' => 0,
            'viewsLast30Days' => 0,
        ];
    }
}
