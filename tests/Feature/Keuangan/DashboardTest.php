<?php

namespace Tests\Feature\Keuangan;

use App\Models\BankKas;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_overview_returns_correct_structure(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        BankKas::create(['nama' => 'Kas', 'tipe' => 'tunai', 'saldo_awal' => 1000000, 'saldo_terkini' => 1000000]);

        $response = $this->getJson('/api/v1/keuangan/dashboard/overview');
        $response->assertOk()->assertJsonStructure([
            'success', 'data' => [
                'total_saldo', 'ringkasan_bank_kas', 'pemasukan_bulan_ini',
                'pengeluaran_bulan_ini', 'selisih_bulan_ini', 'transaksi_terbaru',
            ],
        ]);
    }

    public function test_chart_income_vs_expense(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/keuangan/dashboard/chart/income-vs-expense');
        $response->assertOk()->assertJsonStructure([
            'success', 'data' => [['bulan', 'pemasukan', 'pengeluaran']],
        ]);
    }

    public function test_chart_composition_endpoints(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/keuangan/dashboard/chart/income-composition')->assertOk();
        $this->getJson('/api/v1/keuangan/dashboard/chart/expense-composition')->assertOk();
    }
}
