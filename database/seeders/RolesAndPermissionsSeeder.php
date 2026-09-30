<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'properties.create', 'properties.edit.own', 'properties.edit.any',
            'properties.delete.own', 'properties.delete.any', 'properties.publish',
            'projects.create', 'projects.edit.own', 'projects.edit.any',
            'projects.delete.own', 'projects.delete.any', 'projects.publish',
            'agencies.manage.own', 'agencies.manage.any',
            'agents.invite', 'agents.manage.own', 'agents.manage.any',
            'users.view', 'users.approve', 'users.suspend',
            'categories.manage', 'blogs.manage', 'testimonials.manage',
            'faqs.manage', 'inquiries.view.own', 'inquiries.view.any',
            'settings.manage', 'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        $agency = Role::firstOrCreate(['name' => 'agency']);
        $agency->syncPermissions([
            'properties.create', 'properties.edit.own', 'properties.delete.own',
            'agents.invite', 'agents.manage.own', 'agencies.manage.own',
            'inquiries.view.own',
        ]);

        $agent = Role::firstOrCreate(['name' => 'agent']);
        $agent->syncPermissions([
            'properties.create', 'properties.edit.own', 'properties.delete.own',
            'inquiries.view.own',
        ]);

        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([
            'properties.create', 'properties.edit.own', 'properties.delete.own',
            'inquiries.view.own',
        ]);

        // Create default admin account
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@sarzameen.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'status' => AccountStatus::Approved,
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole('admin');
    }
}
