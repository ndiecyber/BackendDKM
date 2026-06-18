<?php

namespace Tests\Feature\Qurban;

use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\QurbanTransaction;
use App\Models\Qurban\Shohibul;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private QurbanPeriod $period;

    private Shohibul $shohibul;

    private QurbanTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->period = QurbanPeriod::create([
            'name' => 'Test', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);
        $this->shohibul = Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'A', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);
        $this->transaction = QurbanTransaction::create([
            'shohibul_id' => $this->shohibul->id, 'order_id' => 'WH-TEST-001',
            'amount' => 500000, 'status' => 'pending', 'payment_method' => 'qris',
        ]);
    }

    public function test_webhook_rejects_empty_payload(): void
    {
        $this->postJson('/v1/qurban/webhook/pakasir', [])
            ->assertStatus(400);
    }

    public function test_webhook_returns_200_for_unknown_order(): void
    {
        // Should return 200 to prevent PaKasir retries
        $this->postJson('/v1/qurban/webhook/pakasir', [
            'order_id' => 'UNKNOWN', 'status' => 'completed', 'amount' => 100000,
        ])->assertOk();
    }

    public function test_webhook_idempotency(): void
    {
        $this->transaction->update(['status' => 'success']);
        $balanceBefore = $this->shohibul->fresh()->collected_amount;

        $this->postJson('/v1/qurban/webhook/pakasir', [
            'order_id' => 'WH-TEST-001', 'status' => 'completed', 'amount' => 500000,
        ])->assertOk();

        // Balance should NOT increase again
        $this->assertEquals($balanceBefore, $this->shohibul->fresh()->collected_amount);
    }
}
