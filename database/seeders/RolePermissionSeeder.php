<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // User management
            'user.view',
            'user.create',
            'user.update',
            'user.delete',

            // Keuangan module
            'keuangan.view',
            'keuangan.create',
            'keuangan.update',
            'keuangan.delete',
            'keuangan.export',

            // Kurban module
            'kurban.view',
            'kurban.create',
            'kurban.update',
            'kurban.delete',

            // Profile module
            'profile.view',
            'profile.create',
            'profile.update',
            'profile.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        // Super admin gets all permissions via Gate::before in AuthServiceProvider

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'user.view',
            'keuangan.view', 'keuangan.create', 'keuangan.update', 'keuangan.delete', 'keuangan.export',
            'kurban.view', 'kurban.create', 'kurban.update', 'kurban.delete',
            'profile.view', 'profile.create', 'profile.update', 'profile.delete',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'keuangan.view',
            'kurban.view',
            'profile.view',
        ]);
    }
}
