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

            // Jamaah module
            'jamaah.view',
            'jamaah.create',
            'jamaah.update',
            'jamaah.delete',

            // Keuangan module (legacy — kept for backward compatibility)
            'keuangan.view',
            'keuangan.create',
            'keuangan.update',
            'keuangan.delete',
            'keuangan.export',

            // Keuangan module (granular)
            'keuangan.category.view',
            'keuangan.category.create',
            'keuangan.category.update',
            'keuangan.category.delete',

            'keuangan.bank_kas.view',
            'keuangan.bank_kas.create',
            'keuangan.bank_kas.update',
            'keuangan.bank_kas.delete',

            'keuangan.transaksi.view',
            'keuangan.transaksi.create',
            'keuangan.transaksi.update',
            'keuangan.transaksi.delete',
            'keuangan.transaksi.approve',

            'keuangan.laporan.view',
            'keuangan.laporan.export',

            'keuangan.rekonsiliasi.create',

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
            'user.view', 'user.create', 'user.update', 'user.delete',
            'jamaah.view', 'jamaah.create', 'jamaah.update', 'jamaah.delete',
            'keuangan.view', 'keuangan.create', 'keuangan.update', 'keuangan.delete', 'keuangan.export',
            'keuangan.category.view', 'keuangan.category.create', 'keuangan.category.update', 'keuangan.category.delete',
            'keuangan.bank_kas.view', 'keuangan.bank_kas.create', 'keuangan.bank_kas.update', 'keuangan.bank_kas.delete',
            'keuangan.transaksi.view', 'keuangan.transaksi.create', 'keuangan.transaksi.update', 'keuangan.transaksi.delete', 'keuangan.transaksi.approve',
            'keuangan.laporan.view', 'keuangan.laporan.export',
            'keuangan.rekonsiliasi.create',
            'kurban.view', 'kurban.create', 'kurban.update', 'kurban.delete',
            'profile.view', 'profile.create', 'profile.update', 'profile.delete',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'jamaah.view',
            'keuangan.view',
            'keuangan.category.view',
            'keuangan.bank_kas.view',
            'keuangan.transaksi.view',
            'keuangan.laporan.view',
            'kurban.view',
            'profile.view',
        ]);
    }
}
