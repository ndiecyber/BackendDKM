<?php

namespace Tests\Feature\Qurban;

use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\QurbanTransaction;
use App\Models\Qurban\Shohibul;
use App\Models\User;
use App\Services\Qurban\PaKasirService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private QurbanPeriod $period;

    private Shohibul $shohibul;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->period = QurbanPeriod::create([
            'name' => 'Test', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);
        $this->shohibul = Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);
    }

    public function test_deposit_validation(): void
    {
        $this->postJson('/v1/qurban/transactions/deposit', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['shohibul_id', 'amount', 'payment_method']);
    }

    public function test_deposit_rejects_lunas_shohibul(): void
    {
        $this->shohibul->update(['collected_amount' => 4000000]);
        $this->postJson('/v1/qurban/transactions/deposit', [
            'shohibul_id' => $this->shohibul->id, 'amount' => 50000, 'payment_method' => 'qris',
        ])->assertStatus(422)->assertJsonFragment(['success' => false]);
    }

    public function test_deposit_rejects_if_pending_exists(): void
    {
        QurbanTransaction::create([
            'shohibul_id' => $this->shohibul->id, 'order_id' => 'TEST-001',
            'amount' => 100000, 'status' => 'pending', 'payment_method' => 'qris',
        ]);
        $this->postJson('/v1/qurban/transactions/deposit', [
            'shohibul_id' => $this->shohibul->id, 'amount' => 100000, 'payment_method' => 'qris',
        ])->assertStatus(422);
    }

    public function test_deposit_rejects_amount_exceeding_remaining(): void
    {
        $this->shohibul->update(['collected_amount' => 3900000]);
        $this->postJson('/v1/qurban/transactions/deposit', [
            'shohibul_id' => $this->shohibul->id, 'amount' => 200000, 'payment_method' => 'qris',
        ])->assertStatus(422);
    }

    public function test_admin_manual_deposit(): void
    {
        Sanctum::actingAs($this->admin);
        $r = $this->postJson('/v1/qurban/admin/transactions/manual', [
            'shohibul_id' => $this->shohibul->id, 'amount' => 500000,
        ]);
        $r->assertStatus(201);
        $this->assertEquals(500000, $this->shohibul->fresh()->collected_amount);
        $this->assertDatabaseHas('qurban_transactions', [
            'shohibul_id' => $this->shohibul->id, 'status' => 'success', 'payment_method' => 'tunai',
        ]);
    }

    public function test_admin_cancel_pending(): void
    {
        Sanctum::actingAs($this->admin);

        // Mock PaKasir to avoid real HTTP call
        $this->mock(PaKasirService::class, function ($mock) {
            $mock->shouldReceive('cancelTransaction')->once()->andReturn(true);
        });

        $tx = QurbanTransaction::create([
            'shohibul_id' => $this->shohibul->id, 'order_id' => 'TEST-CANCEL',
            'amount' => 100000, 'status' => 'pending', 'payment_method' => 'qris',
        ]);
        $this->postJson("/v1/qurban/admin/transactions/{$tx->id}/cancel")->assertOk();
        $this->assertEquals('cancelled', $tx->fresh()->status);
    }

    public function test_cancel_requires_auth(): void
    {
        $tx = QurbanTransaction::create([
            'shohibul_id' => $this->shohibul->id, 'order_id' => 'TEST-NOAUTH',
            'amount' => 100000, 'status' => 'pending', 'payment_method' => 'qris',
        ]);
        $this->postJson("/v1/qurban/admin/transactions/{$tx->id}/cancel")->assertStatus(401);
    }

    public function test_list_transactions(): void
    {
        QurbanTransaction::create([
            'shohibul_id' => $this->shohibul->id, 'order_id' => 'TX-1',
            'amount' => 100000, 'status' => 'success', 'payment_method' => 'qris',
        ]);
        $this->getJson('/v1/qurban/transactions')->assertOk()->assertJsonPath('data.total', 1);
    }
}
