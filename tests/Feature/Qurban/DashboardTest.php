<?php

namespace Tests\Feature\Qurban;

use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\Shohibul;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_returns_correct_aggregates(): void
    {
        $period = QurbanPeriod::create([
            'name' => 'Test', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);

        Shohibul::create([
            'period_id' => $period->id, 'name' => 'A', 'phone' => '081', 'address' => 'X',
            'target_type' => 'sapi', 'target_amount' => 4000000, 'collected_amount' => 4000000,
        ]);
        Shohibul::create([
            'period_id' => $period->id, 'name' => 'B', 'phone' => '082', 'address' => 'Y',
            'target_type' => 'sapi', 'target_amount' => 4000000, 'collected_amount' => 1000000,
        ]);

        $r = $this->getJson('/v1/qurban/dashboard/stats');
        $r->assertOk()
            ->assertJsonPath('data.summary.total_shohibul', 2)
            ->assertJsonPath('data.summary.count_lunas', 1)
            ->assertJsonPath('data.summary.count_belum_lunas', 1);
    }

    public function test_stats_returns_404_without_active_period(): void
    {
        $this->getJson('/v1/qurban/dashboard/stats')->assertStatus(404);
    }
}
