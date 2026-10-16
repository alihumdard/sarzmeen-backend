<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    /**
     * Groups the unauthenticated /site-settings/{group} route may return.
     * `email` and `system` stay admin-only — they describe infrastructure.
     */
    private const PUBLIC_GROUPS = ['homepage', 'seo', 'appearance', 'general'];

    public function index(Request $request, string $group): JsonResponse
    {
        // The same method serves the public route and the admin one.
        if (! $request->user() && ! in_array($group, self::PUBLIC_GROUPS, true)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $settings = SiteSetting::getGroup($group);

        return response()->json(['data' => $settings]);
    }

    public function show(string $group, string $key): JsonResponse
    {
        $value = SiteSetting::getValue($group, $key);

        return response()->json(['data' => $value]);
    }

    public function update(Request $request, string $group): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $item) {
            SiteSetting::setValue($group, $item['key'], $item['value']);
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => SiteSetting::getGroup($group),
        ]);
    }

    public function updateKey(Request $request, string $group, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required',
        ]);

        SiteSetting::setValue($group, $key, $validated['value']);

        return response()->json([
            'message' => 'Setting updated successfully.',
            'data' => SiteSetting::getValue($group, $key),
        ]);
    }
}
