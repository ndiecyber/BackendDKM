<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default super admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@dkm.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $superAdmin->forceFill([
            'username' => 'superadmin',
        ])->save();

        $superAdmin->assignRole('super-admin');

        // Create Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin_biasa@dkm.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        $admin->forceFill([
            'username' => 'admin',
        ])->save();

        $admin->assignRole('admin');

        // Create Bendahara user
        $bendahara = User::firstOrCreate(
            ['email' => 'bendahara@dkm.local'],
            [
                'name' => 'Bendahara',
                'password' => Hash::make('password'),
            ]
        );

        $bendahara->forceFill([
            'username' => 'bendahara',
        ])->save();

        $bendahara->assignRole('bendahara');

        // Create Sekretaris user
        $sekretaris = User::firstOrCreate(
            ['email' => 'sekretaris@dkm.local'],
            [
                'name' => 'Sekretaris',
                'password' => Hash::make('password'),
            ]
        );

        $sekretaris->forceFill([
            'username' => 'sekretaris',
        ])->save();

        $sekretaris->assignRole('sekretaris');
    }
}
