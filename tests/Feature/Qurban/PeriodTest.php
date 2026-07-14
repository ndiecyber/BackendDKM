<?php

namespace Tests\Feature\Qurban;

use App\Models\Qurban\QurbanPeriod;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_get_active_period(): void
    {
        QurbanPeriod::create([
            'name' => 'Qurban 1448 H', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);

        $r = $this->getJson('/v1/qurban/config/active');
        $r->assertOk()
            ->assertJsonPath('data.period.name', 'Qurban 1448 H')
            ->assertJsonPath('data.period.sapi_price_per_slot', '4000000.00');
    }

    public function test_get_active_period_returns_404_when_none(): void
    {
        $this->getJson('/v1/qurban/config/active')->assertStatus(404);
    }

    public function test_admin_create_period(): void
    {
        Sanctum::actingAs($this->admin);

        $r = $this->postJson('/v1/qurban/admin/periods', [
            'name' => 'Qurban 1449 H', 'sapi_price_per_slot' => 4500000,
            'kambing_price' => 4000000, 'deadline_date' => now()->addYear()->toDateString(),
        ]);

        $r->assertStatus(201)->assertJsonPath('data.name', 'Qurban 1449 H');
        $this->assertDatabaseHas('qurban_periods', ['name' => 'Qurban 1449 H']);
    }

    public function test_create_period_requires_auth(): void
    {
        $this->postJson('/v1/qurban/admin/periods', [
            'name' => 'Test', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2028-01-01',
        ])->assertStatus(401);
    }

    public function test_admin_update_active_period(): void
    {
        Sanctum::actingAs($this->admin);

        QurbanPeriod::create([
            'name' => 'Qurban 1448 H', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);

        $r = $this->putJson('/v1/qurban/admin/periods/active', [
            'name' => 'Qurban 1448 H Updated', 'sapi_price_per_slot' => 4500000,
            'kambing_price' => 3500000, 'deadline_date' => now()->addYear()->toDateString(),
        ]);

        $r->assertOk()->assertJsonPath('data.sapi_price_per_slot', '4500000.00');
    }

    public function test_admin_list_periods(): void
    {
        Sanctum::actingAs($this->admin);

        QurbanPeriod::create([
            'name' => 'Period 1', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => false,
        ]);
        QurbanPeriod::create([
            'name' => 'Period 2', 'sapi_price_per_slot' => 4500000,
            'kambing_price' => 4000000, 'deadline_date' => '2028-06-01', 'is_active' => true,
        ]);

        $r = $this->getJson('/v1/qurban/admin/periods');
        $r->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_update_period_cascades_price_to_shohibuls(): void
    {
        Sanctum::actingAs($this->admin);

        $period = QurbanPeriod::create([
            'name' => 'Test', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);

        // Create shohibuls
        $period->shohibuls()->create([
            'name' => 'A', 'phone' => '081', 'address' => 'X',
            'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);
        $period->shohibuls()->create([
            'name' => 'B', 'phone' => '082', 'address' => 'Y',
            'target_type' => 'kambing', 'target_amount' => 3500000,
        ]);

        // Update price
        $this->putJson('/v1/qurban/admin/periods/active', [
            'name' => 'Test', 'sapi_price_per_slot' => 4500000,
            'kambing_price' => 3500000, 'deadline_date' => now()->addYear()->toDateString(),
        ])->assertOk();

        // Sapi shohibul target should be updated
        $this->assertDatabaseHas('shohibuls', ['name' => 'A', 'target_amount' => 4500000]);
        // Kambing unchanged
        $this->assertDatabaseHas('shohibuls', ['name' => 'B', 'target_amount' => 3500000]);
    }
}
