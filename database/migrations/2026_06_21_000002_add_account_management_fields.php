<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->unsignedInteger('hierarchy')->nullable()->after('guard_name');
            $table->json('modules')->nullable()->after('hierarchy');
        });

        DB::table('roles')->where('name', 'super-admin')->update([
            'display_name' => 'Super Admin',
            'hierarchy' => 1,
            'modules' => json_encode(['web', 'keuangan', 'qurban', 'sistem']),
        ]);

        DB::table('roles')->where('name', 'admin')->update([
            'display_name' => 'Admin',
            'hierarchy' => 2,
            'modules' => json_encode(['web', 'keuangan', 'qurban', 'sistem']),
        ]);

        DB::table('roles')->where('name', 'viewer')->update([
            'display_name' => 'Viewer',
            'hierarchy' => 99,
            'modules' => json_encode([]),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'hierarchy', 'modules']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
