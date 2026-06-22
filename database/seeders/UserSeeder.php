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
            'username' => $superAdmin->username ?? 'admin',
        ])->save();

        $superAdmin->assignRole('super-admin');
    }
}
