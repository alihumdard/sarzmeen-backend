<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const LABELS = [
        'admin' => 'Administrator',
        'agency' => 'Agency',
        'agent' => 'Agent',
        'user' => 'User',
    ];

    private const DESCRIPTIONS = [
        'admin' => 'Full system access with all permissions.',
        'agency' => 'Manages its own listings, agents and enquiries.',
        'agent' => 'Manages its own property listings.',
        'user' => 'Can list and manage their own properties.',
    ];

    public function index(): JsonResponse
    {
        // Counted per role rather than with withCount('users'): spatie cannot
        // resolve the user model through the relation in an HTTP request.
        $roles = Role::with('permissions')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => self::LABELS[$role->name] ?? Str::headline($role->name),
                'description' => self::DESCRIPTIONS[$role->name] ?? '',
                'usersCount' => User::role($role->name)->count(),
                'permissions' => $role->permissions->pluck('name')->all(),
                // Distinct modules the role can touch, for the summary chips.
                'modules' => $role->permissions
                    ->map(fn ($p) => Str::headline(Str::before($p->name, '.')))
                    ->unique()->values()->all(),
                'createdAt' => $role->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $roles]);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json(['data' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->all(),
        ]]);
    }

    public function updatePermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // Without this an admin can remove `roles.manage` from their own role
        // and lock every administrator out of this screen permanently.
        if ($role->name === 'admin' && ! in_array('roles.manage', $validated['permissions'], true)) {
            return response()->json([
                'message' => 'The admin role must keep "roles.manage", otherwise no one could edit roles again.',
                'errors' => ['permissions' => ['The admin role must keep "roles.manage".']],
            ], 422);
        }

        $before = $role->permissions->pluck('name')->sort()->values()->all();

        $role->syncPermissions($validated['permissions']);

        $after = $role->fresh()->permissions->pluck('name')->sort()->values()->all();

        if ($before !== $after) {
            activity('role')
                ->causedBy($request->user())
                ->performedOn($role)
                ->event('permissions_updated')
                ->withProperties([
                    'role' => $role->name,
                    'added' => array_values(array_diff($after, $before)),
                    'removed' => array_values(array_diff($before, $after)),
                ])
                ->log('permissions_updated');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['data' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->all(),
        ]]);
    }

    /**
     * Grouped by the part before the first dot ("properties.edit.own" ->
     * "properties"), so the UI can render one section per module instead of a
     * flat list of 28 checkboxes.
     */
    public function permissions(): JsonResponse
    {
        $groups = Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $p) => Str::before($p->name, '.'))
            ->map(fn ($items, $group) => [
                'group' => $group,
                'label' => Str::headline($group),
                'permissions' => $items->map(fn (Permission $p) => [
                    'name' => $p->name,
                    'label' => Str::headline(Str::after($p->name, '.')),
                ])->values()->all(),
            ])
            ->values();

        return response()->json(['data' => $groups]);
    }
}
