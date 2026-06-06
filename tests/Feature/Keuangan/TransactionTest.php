<?php

namespace Tests\Feature\Keuangan;

use App\Models\BankKas;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private BankKas $kasTunai;

    private BankKas $kasBank;

    private Category $kategoriPemasukan;

    private Category $kategoriPengeluaran;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->kasTunai = BankKas::create(['nama' => 'Kas Tunai', 'tipe' => 'tunai', 'saldo_awal' => 1000000, 'saldo_terkini' => 1000000]);
        $this->kasBank = BankKas::create(['nama' => 'BSI', 'tipe' => 'rekening', 'saldo_awal' => 5000000, 'saldo_terkini' => 5000000]);
        $this->kategoriPemasukan = Category::create(['nama' => 'Infaq', 'tipe' => 'pemasukan']);
        $this->kategoriPengeluaran = Category::create(['nama' => 'Operasional', 'tipe' => 'pengeluaran']);
    }

    public function test_create_income(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => 'Infaq Jumat', 'nominal' => 500000,
            'tanggal' => '2026-06-05', 'bank_kas_tujuan_id' => $this->kasTunai->id,
            'category_id' => $this->kategoriPemasukan->id, 'status' => 'draft',
        ]);
        $r->assertStatus(201)->assertJson(['success' => true, 'data' => ['tipe' => 'pemasukan']]);
        $this->assertStringStartsWith('IN-20260605-', $r->json('data.nomor_transaksi'));
    }

    public function test_create_expense(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pengeluaran', 'nama' => 'Beli Sapu', 'nominal' => 50000,
            'tanggal' => '2026-06-05', 'bank_kas_asal_id' => $this->kasTunai->id,
            'category_id' => $this->kategoriPengeluaran->id, 'status' => 'draft',
        ]);
        $r->assertStatus(201);
        $this->assertStringStartsWith('OUT-20260605-', $r->json('data.nomor_transaksi'));
    }

    public function test_create_transfer(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'transfer', 'nama' => 'Setor', 'nominal' => 500000,
            'tanggal' => '2026-06-05', 'bank_kas_asal_id' => $this->kasTunai->id,
            'bank_kas_tujuan_id' => $this->kasBank->id, 'biaya_admin' => 2500, 'status' => 'draft',
        ]);
        $r->assertStatus(201);
        $this->assertStringStartsWith('TRF-20260605-', $r->json('data.nomor_transaksi'));
    }

    public function test_transfer_requires_different_accounts(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'transfer', 'nama' => 'Bad', 'nominal' => 100000, 'tanggal' => '2026-06-05',
            'bank_kas_asal_id' => $this->kasTunai->id, 'bank_kas_tujuan_id' => $this->kasTunai->id,
        ]);
        $r->assertStatus(422);
    }

    public function test_saldo_updates_on_approval(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => 'Infaq', 'nominal' => 200000, 'tanggal' => '2026-06-05',
            'bank_kas_tujuan_id' => $this->kasTunai->id, 'status' => 'draft',
        ]);
        $id = $r->json('data.id');
        $this->kasTunai->refresh();
        $this->assertEquals(1000000, $this->kasTunai->saldo_terkini);

        $this->patchJson("/v1/keuangan/transactions/{$id}/status", ['status' => 'approved'])->assertOk();
        $this->kasTunai->refresh();
        $this->assertEquals(1200000, $this->kasTunai->saldo_terkini);
    }

    public function test_saldo_reverts_on_delete(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pengeluaran', 'nama' => 'ATK', 'nominal' => 100000, 'tanggal' => '2026-06-05',
            'bank_kas_asal_id' => $this->kasTunai->id, 'status' => 'approved',
        ]);
        $id = $r->json('data.id');
        $this->kasTunai->refresh();
        $this->assertEquals(900000, $this->kasTunai->saldo_terkini);

        $this->deleteJson("/v1/keuangan/transactions/{$id}");
        $this->kasTunai->refresh();
        $this->assertEquals(1000000, $this->kasTunai->saldo_terkini);
    }

    public function test_cannot_edit_approved(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => 'Approved', 'nominal' => 100000, 'tanggal' => '2026-06-05',
            'bank_kas_tujuan_id' => $this->kasTunai->id, 'status' => 'approved',
        ]);
        $this->putJson("/v1/keuangan/transactions/{$r->json('data.id')}", ['nama' => 'Edit'])->assertStatus(422);
    }

    public function test_status_workflow(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => 'Test', 'nominal' => 100000, 'tanggal' => '2026-06-05',
            'bank_kas_tujuan_id' => $this->kasTunai->id, 'status' => 'approved',
        ]);
        // approved → draft is not allowed
        $this->patchJson("/v1/keuangan/transactions/{$r->json('data.id')}/status", ['status' => 'draft'])->assertStatus(422);
    }

    public function test_filters(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => 'A', 'nominal' => 100000, 'tanggal' => '2026-06-05',
            'bank_kas_tujuan_id' => $this->kasTunai->id, 'status' => 'draft',
        ]);
        $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pengeluaran', 'nama' => 'B', 'nominal' => 50000, 'tanggal' => '2026-06-05',
            'bank_kas_asal_id' => $this->kasTunai->id, 'status' => 'draft',
        ]);
        $r = $this->getJson('/v1/keuangan/transactions?tipe=pemasukan');
        $r->assertOk();
        $this->assertCount(1, $r->json('data.data'));
    }

    public function test_auto_increment(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => '1st', 'nominal' => 100000, 'tanggal' => '2026-06-05',
            'bank_kas_tujuan_id' => $this->kasTunai->id, 'status' => 'draft',
        ]);
        $r = $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => '2nd', 'nominal' => 200000, 'tanggal' => '2026-06-05',
            'bank_kas_tujuan_id' => $this->kasTunai->id, 'status' => 'draft',
        ]);
        $this->assertEquals('IN-20260605-0002', $r->json('data.nomor_transaksi'));
    }

    public function test_viewer_cannot_create(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);
        $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => 'X', 'nominal' => 100000, 'tanggal' => '2026-06-05',
            'bank_kas_tujuan_id' => $this->kasTunai->id,
        ])->assertStatus(403);
    }

    public function test_income_requires_tujuan(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/v1/keuangan/transactions', [
            'tipe' => 'pemasukan', 'nama' => 'No Tujuan', 'nominal' => 100000, 'tanggal' => '2026-06-05',
        ])->assertStatus(422)->assertJsonValidationErrors('bank_kas_tujuan_id');
    }
}
