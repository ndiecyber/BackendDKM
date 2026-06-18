<?php

namespace Tests\Feature\Qurban;

use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\Shohibul;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShohibulTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private QurbanPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->period = QurbanPeriod::create([
            'name' => 'Qurban 1448 H / 2027 M',
            'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000,
            'deadline_date' => now()->addMonths(6)->toDateString(),
            'is_active' => true,
        ]);
    }

    // ────────────────────────────────────────
    // Public: GET /v1/qurban/shohibuls
    // ────────────────────────────────────────

    public function test_index_returns_shohibuls_for_active_period(): void
    {
        Shohibul::create([
            'period_id' => $this->period->id,
            'name' => 'Ahmad',
            'phone' => '081234567890',
            'address' => 'Blok A1',
            'target_type' => 'sapi',
            'target_amount' => 4000000,
        ]);

        $r = $this->getJson('/v1/qurban/shohibuls');
        $r->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ahmad');
    }

    public function test_index_returns_404_when_no_active_period(): void
    {
        $this->period->update(['is_active' => false]);

        $this->getJson('/v1/qurban/shohibuls')->assertStatus(404);
    }

    public function test_index_search_filter(): void
    {
        Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);
        Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Budi', 'phone' => '082',
            'address' => 'Blok B', 'target_type' => 'kambing', 'target_amount' => 3500000,
        ]);

        // Search by name
        $r = $this->getJson('/v1/qurban/shohibuls?search=Ahmad');
        $r->assertOk()->assertJsonCount(1, 'data');

        // Filter by type
        $r = $this->getJson('/v1/qurban/shohibuls?type=kambing');
        $r->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Budi');
    }

    // ────────────────────────────────────────
    // Public: GET /v1/qurban/shohibuls/search
    // ────────────────────────────────────────

    public function test_search_autocomplete(): void
    {
        Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad Nasai', 'phone' => '081',
            'address' => 'Blok C1.32', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);

        $r = $this->getJson('/v1/qurban/shohibuls/search?q=Nasai');
        $r->assertOk()->assertJsonCount(1, 'data');
        $r->assertJsonPath('data.0.name', 'Ahmad Nasai');
    }

    public function test_search_returns_empty_without_query(): void
    {
        $this->getJson('/v1/qurban/shohibuls/search')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ────────────────────────────────────────
    // Public: GET /v1/qurban/shohibuls/{id}
    // ────────────────────────────────────────

    public function test_show_shohibul_detail(): void
    {
        $shohibul = Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);

        $r = $this->getJson("/v1/qurban/shohibuls/{$shohibul->id}");
        $r->assertOk()
            ->assertJsonPath('data.name', 'Ahmad')
            ->assertJsonPath('data.is_lunas', false)
            ->assertJsonPath('data.remaining_amount', '4000000.00');
    }

    // ────────────────────────────────────────
    // Public: POST /v1/qurban/shohibuls/register
    // ────────────────────────────────────────

    public function test_register_validation_errors(): void
    {
        $r = $this->postJson('/v1/qurban/shohibuls/register', []);
        $r->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'address', 'target_type', 'initial_amount', 'payment_method']);
    }

    public function test_register_rejects_invalid_amount(): void
    {
        // amount not multiple of 50000
        $r = $this->postJson('/v1/qurban/shohibuls/register', [
            'name' => 'Test', 'phone' => '081', 'address' => 'A',
            'target_type' => 'sapi', 'initial_amount' => 75000, 'payment_method' => 'qris',
        ]);
        $r->assertStatus(422)->assertJsonValidationErrors('initial_amount');
    }

    public function test_register_rejects_amount_below_minimum(): void
    {
        $r = $this->postJson('/v1/qurban/shohibuls/register', [
            'name' => 'Test', 'phone' => '081', 'address' => 'A',
            'target_type' => 'sapi', 'initial_amount' => 25000, 'payment_method' => 'qris',
        ]);
        $r->assertStatus(422)->assertJsonValidationErrors('initial_amount');
    }

    public function test_register_rejects_duplicate_name_phone(): void
    {
        Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081234',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);

        // Mock PaKasir is not needed — duplication check happens before PaKasir call
        $r = $this->postJson('/v1/qurban/shohibuls/register', [
            'name' => 'Ahmad', 'phone' => '081234', 'address' => 'Blok B',
            'target_type' => 'sapi', 'initial_amount' => 100000, 'payment_method' => 'qris',
        ]);
        $r->assertStatus(422)->assertJsonFragment(['success' => false]);
    }

    // ────────────────────────────────────────
    // Admin: PUT /v1/qurban/admin/shohibuls/{id}
    // ────────────────────────────────────────

    public function test_admin_update_shohibul(): void
    {
        Sanctum::actingAs($this->admin);

        $shohibul = Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);

        $r = $this->putJson("/v1/qurban/admin/shohibuls/{$shohibul->id}", [
            'name' => 'Ahmad Updated',
            'phone' => '089999',
        ]);

        $r->assertOk()->assertJsonPath('data.name', 'Ahmad Updated');
    }

    public function test_update_requires_auth(): void
    {
        $shohibul = Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);

        $this->putJson("/v1/qurban/admin/shohibuls/{$shohibul->id}", ['name' => 'X'])
            ->assertStatus(401);
    }

    // ────────────────────────────────────────
    // Admin: DELETE /v1/qurban/admin/shohibuls/{id}
    // ────────────────────────────────────────

    public function test_admin_delete_shohibul(): void
    {
        Sanctum::actingAs($this->admin);

        $shohibul = Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
            'collected_amount' => 0,
        ]);

        $this->deleteJson("/v1/qurban/admin/shohibuls/{$shohibul->id}")->assertOk();
        $this->assertSoftDeleted('shohibuls', ['id' => $shohibul->id]);
    }

    public function test_delete_blocked_if_has_balance(): void
    {
        Sanctum::actingAs($this->admin);

        $shohibul = Shohibul::create([
            'period_id' => $this->period->id, 'name' => 'Ahmad', 'phone' => '081',
            'address' => 'Blok A', 'target_type' => 'sapi', 'target_amount' => 4000000,
            'collected_amount' => 500000,
        ]);

        $this->deleteJson("/v1/qurban/admin/shohibuls/{$shohibul->id}")->assertStatus(422);
    }
}
