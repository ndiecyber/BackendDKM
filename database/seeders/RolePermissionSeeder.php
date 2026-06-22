<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
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

            // Kurban module (legacy — kept for backward compatibility)
            'kurban.view',
            'kurban.create',
            'kurban.update',
            'kurban.delete',

            // Kurban/Qurban module (granular)
            'qurban.periode.view',
            'qurban.periode.create',
            'qurban.periode.update',

            'qurban.shohibul.view',
            'qurban.shohibul.create',
            'qurban.shohibul.update',
            'qurban.shohibul.delete',

            'qurban.transaksi.view',
            'qurban.transaksi.create',
            'qurban.transaksi.cancel',

            'qurban.kelompok.view',
            'qurban.kelompok.create',
            'qurban.kelompok.update',

            'qurban.rollover.execute',

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
        $superAdmin->forceFill([
            'display_name' => 'Super Admin',
            'hierarchy' => 1,
            'modules' => ['web', 'keuangan', 'qurban', 'sistem'],
        ])->save();
        // Super admin gets all permissions via Gate::before in AuthServiceProvider

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->forceFill([
            'display_name' => 'Admin',
            'hierarchy' => 2,
            'modules' => ['web', 'keuangan', 'qurban', 'sistem'],
        ])->save();
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
            'qurban.periode.view', 'qurban.periode.create', 'qurban.periode.update',
            'qurban.shohibul.view', 'qurban.shohibul.create', 'qurban.shohibul.update', 'qurban.shohibul.delete',
            'qurban.transaksi.view', 'qurban.transaksi.create', 'qurban.transaksi.cancel',
            'qurban.kelompok.view', 'qurban.kelompok.create', 'qurban.kelompok.update',
            'qurban.rollover.execute',
            'profile.view', 'profile.create', 'profile.update', 'profile.delete',
        ]);

        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->forceFill([
            'display_name' => 'Bendahara',
            'hierarchy' => 3,
            'modules' => ['keuangan'],
        ])->save();
        $bendahara->syncPermissions(Role::permissionsForModules(['keuangan']));

        $sekretaris = Role::firstOrCreate(['name' => 'sekretaris']);
        $sekretaris->forceFill([
            'display_name' => 'Sekretaris',
            'hierarchy' => 4,
            'modules' => ['web'],
        ])->save();
        $sekretaris->syncPermissions(Role::permissionsForModules(['web']));

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->forceFill([
            'display_name' => 'Viewer',
            'hierarchy' => 99,
            'modules' => [],
        ])->save();
        $viewer->syncPermissions([
            'jamaah.view',
            'keuangan.view',
            'keuangan.category.view',
            'keuangan.bank_kas.view',
            'keuangan.transaksi.view',
            'keuangan.laporan.view',
            'kurban.view',
            'qurban.periode.view',
            'qurban.shohibul.view',
            'qurban.transaksi.view',
            'qurban.kelompok.view',
            'profile.view',
        ]);
    }
}
