<?php

namespace Tests\Feature\Qurban;

use App\Models\Qurban\AnimalGroup;
use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\Shohibul;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnimalGroupTest extends TestCase
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
            'name' => 'Test', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);
    }

    public function test_list_groups(): void
    {
        AnimalGroup::create(['period_id' => $this->period->id, 'name' => 'Sapi 1', 'target_type' => 'sapi']);
        $this->getJson('/v1/qurban/groups')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_create_group(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson('/v1/qurban/admin/groups', ['name' => 'Sapi 99', 'target_type' => 'sapi'])
            ->assertStatus(201);
    }

    public function test_move_member(): void
    {
        Sanctum::actingAs($this->admin);
        $g1 = AnimalGroup::create(['period_id' => $this->period->id, 'name' => 'Sapi 1', 'target_type' => 'sapi']);
        $g2 = AnimalGroup::create(['period_id' => $this->period->id, 'name' => 'Sapi 2', 'target_type' => 'sapi']);
        $s = Shohibul::create([
            'period_id' => $this->period->id, 'animal_group_id' => $g1->id,
            'name' => 'A', 'phone' => '081', 'address' => 'X', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);
        $this->postJson('/v1/qurban/admin/groups/move-member', [
            'shohibul_id' => $s->id, 'new_group_id' => $g2->id,
        ])->assertOk();
        $this->assertEquals($g2->id, $s->fresh()->animal_group_id);
    }

    public function test_cannot_move_to_wrong_type(): void
    {
        Sanctum::actingAs($this->admin);
        $sapi = AnimalGroup::create(['period_id' => $this->period->id, 'name' => 'Sapi 1', 'target_type' => 'sapi']);
        $kambing = AnimalGroup::create(['period_id' => $this->period->id, 'name' => 'Kambing', 'target_type' => 'kambing']);
        $s = Shohibul::create([
            'period_id' => $this->period->id, 'animal_group_id' => $sapi->id,
            'name' => 'A', 'phone' => '081', 'address' => 'X', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);
        $this->postJson('/v1/qurban/admin/groups/move-member', [
            'shohibul_id' => $s->id, 'new_group_id' => $kambing->id,
        ])->assertStatus(422);
    }

    public function test_cannot_move_to_full_group(): void
    {
        Sanctum::actingAs($this->admin);
        $full = AnimalGroup::create(['period_id' => $this->period->id, 'name' => 'Sapi 1', 'target_type' => 'sapi']);
        for ($i = 1; $i <= 7; $i++) {
            Shohibul::create([
                'period_id' => $this->period->id, 'animal_group_id' => $full->id,
                'name' => "M{$i}", 'phone' => "08{$i}", 'address' => 'A', 'target_type' => 'sapi', 'target_amount' => 4000000,
            ]);
        }
        $g2 = AnimalGroup::create(['period_id' => $this->period->id, 'name' => 'Sapi 2', 'target_type' => 'sapi']);
        $s = Shohibul::create([
            'period_id' => $this->period->id, 'animal_group_id' => $g2->id,
            'name' => 'New', 'phone' => '089', 'address' => 'B', 'target_type' => 'sapi', 'target_amount' => 4000000,
        ]);
        $this->postJson('/v1/qurban/admin/groups/move-member', [
            'shohibul_id' => $s->id, 'new_group_id' => $full->id,
        ])->assertStatus(422);
    }
}
