<?php

namespace Tests\Feature\Keuangan;

use App\Models\BankKas;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private BankKas $kasTunai;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->kasTunai = BankKas::create(['nama' => 'Kas', 'tipe' => 'tunai', 'saldo_awal' => 0, 'saldo_terkini' => 0]);
    }

    public function test_buku_kas_umum_with_running_balance(): void
    {
        Sanctum::actingAs($this->admin);
        $cat = Category::create(['nama' => 'Infaq', 'tipe' => 'pemasukan']);

        Transaction::create([
            'nomor_transaksi' => 'IN-20260601-0001', 'tipe' => 'pemasukan', 'nama' => 'A',
            'nominal' => 500000, 'tanggal' => '2026-06-01', 'bank_kas_tujuan_id' => $this->kasTunai->id,
            'category_id' => $cat->id, 'status' => 'approved', 'created_by' => $this->admin->id,
        ]);
        Transaction::create([
            'nomor_transaksi' => 'IN-20260602-0001', 'tipe' => 'pemasukan', 'nama' => 'B',
            'nominal' => 300000, 'tanggal' => '2026-06-02', 'bank_kas_tujuan_id' => $this->kasTunai->id,
            'category_id' => $cat->id, 'status' => 'approved', 'created_by' => $this->admin->id,
        ]);

        $r = $this->getJson('/api/v1/keuangan/reports/buku-kas?tanggal_mulai=2026-06-01&tanggal_akhir=2026-06-30');
        $r->assertOk();

        $entries = $r->json('data.entries');
        $this->assertCount(2, $entries);
        $this->assertEquals(500000, $entries[0]['saldo']); // running balance
        $this->assertEquals(800000, $entries[1]['saldo']);
        $this->assertEquals(800000, $r->json('data.summary.total_masuk'));
    }

    public function test_rekap_kategori(): void
    {
        Sanctum::actingAs($this->admin);
        $cat1 = Category::create(['nama' => 'Infaq', 'tipe' => 'pemasukan']);
        $cat2 = Category::create(['nama' => 'Operasional', 'tipe' => 'pengeluaran']);

        Transaction::create([
            'nomor_transaksi' => 'IN-20260601-0001', 'tipe' => 'pemasukan', 'nama' => 'A',
            'nominal' => 500000, 'tanggal' => '2026-06-01', 'bank_kas_tujuan_id' => $this->kasTunai->id,
            'category_id' => $cat1->id, 'status' => 'approved', 'created_by' => $this->admin->id,
        ]);
        Transaction::create([
            'nomor_transaksi' => 'OUT-20260601-0001', 'tipe' => 'pengeluaran', 'nama' => 'B',
            'nominal' => 200000, 'tanggal' => '2026-06-01', 'bank_kas_asal_id' => $this->kasTunai->id,
            'category_id' => $cat2->id, 'status' => 'approved', 'created_by' => $this->admin->id,
        ]);

        $r = $this->getJson('/api/v1/keuangan/reports/rekap-kategori?tanggal_mulai=2026-06-01&tanggal_akhir=2026-06-30');
        $r->assertOk();
        $this->assertEquals(500000, $r->json('data.pemasukan.total'));
        $this->assertEquals(200000, $r->json('data.pengeluaran.total'));
        $this->assertEquals(300000, $r->json('data.selisih'));
    }

    public function test_export_csv(): void
    {
        Sanctum::actingAs($this->admin);

        $r = $this->get('/api/v1/keuangan/reports/export/csv?tanggal_mulai=2026-06-01&tanggal_akhir=2026-06-30');
        $r->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_viewer_cannot_export(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $this->get('/api/v1/keuangan/reports/export/csv')->assertStatus(403);
    }
}
