<?php

namespace Tests\Feature\Keuangan;

use App\Models\BankKas;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BankKasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_list_bank_kas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        BankKas::create(['nama' => 'Kas Tunai', 'tipe' => 'tunai', 'saldo_awal' => 0, 'saldo_terkini' => 0]);

        $response = $this->getJson('/api/v1/keuangan/bank-kas');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'nama', 'tipe', 'saldo_awal', 'saldo_terkini', 'status'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_bank_kas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/keuangan/bank-kas', [
            'nama' => 'BSI Operasional',
            'tipe' => 'rekening',
            'nomor_rekening' => '1234567890',
            'atas_nama' => 'DKM Masjid',
            'saldo_awal' => 5000000,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama' => 'BSI Operasional',
                    'tipe' => 'rekening',
                    'saldo_terkini' => '5000000.00',
                ],
            ]);

        $this->assertDatabaseHas('bank_kas', ['nama' => 'BSI Operasional']);
    }

    public function test_admin_can_update_bank_kas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $bankKas = BankKas::create([
            'nama' => 'Old Name',
            'tipe' => 'tunai',
            'saldo_awal' => 0,
            'saldo_terkini' => 0,
        ]);

        $response = $this->putJson("/api/v1/keuangan/bank-kas/{$bankKas->id}", [
            'nama' => 'Updated Name',
            'status' => 'non_aktif',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['nama' => 'Updated Name'],
            ]);
    }

    public function test_admin_can_soft_delete_and_restore_bank_kas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $bankKas = BankKas::create([
            'nama' => 'To Delete',
            'tipe' => 'tunai',
            'saldo_awal' => 0,
            'saldo_terkini' => 0,
        ]);

        $response = $this->deleteJson("/api/v1/keuangan/bank-kas/{$bankKas->id}");
        $response->assertOk();
        $this->assertSoftDeleted('bank_kas', ['id' => $bankKas->id]);

        $response = $this->patchJson("/api/v1/keuangan/bank-kas/{$bankKas->id}/restore");
        $response->assertOk();
        $this->assertDatabaseHas('bank_kas', ['id' => $bankKas->id, 'deleted_at' => null]);
    }

    public function test_admin_can_adjust_balance(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $bankKas = BankKas::create([
            'nama' => 'Kas Tunai',
            'tipe' => 'tunai',
            'saldo_awal' => 1000000,
            'saldo_terkini' => 1000000,
        ]);

        $response = $this->postJson("/api/v1/keuangan/bank-kas/{$bankKas->id}/adjust", [
            'saldo_sesudah' => 950000,
            'deskripsi' => 'Penyesuaian setelah hitung kas fisik',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'saldo_sebelum' => '1000000.00',
                    'saldo_sesudah' => '950000.00',
                    'selisih' => '-50000.00',
                ],
            ]);

        $this->assertDatabaseHas('balance_adjustments', [
            'bank_kas_id' => $bankKas->id,
        ]);
    }

    public function test_viewer_cannot_create_bank_kas(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $response = $this->postJson('/api/v1/keuangan/bank-kas', [
            'nama' => 'Test',
            'tipe' => 'tunai',
            'saldo_awal' => 0,
        ]);

        $response->assertStatus(403);
    }
}
