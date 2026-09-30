<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'usersCount' => $role->users_count,
                'keyPermissions' => $role->permissions->pluck('name')->all(),
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
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['data' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->all(),
        ]]);
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::all()->pluck('name');

        return response()->json(['data' => $permissions]);
    }
}
