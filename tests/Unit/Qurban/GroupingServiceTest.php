<?php

namespace Tests\Unit\Qurban;

use App\Models\Qurban\AnimalGroup;
use App\Models\Qurban\QurbanPeriod;
use App\Models\Qurban\Shohibul;
use App\Services\Qurban\GroupingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupingServiceTest extends TestCase
{
    use RefreshDatabase;

    private GroupingService $service;

    private QurbanPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GroupingService;
        $this->period = QurbanPeriod::create([
            'name' => 'Test', 'sapi_price_per_slot' => 4000000,
            'kambing_price' => 3500000, 'deadline_date' => '2027-06-01', 'is_active' => true,
        ]);
    }

    public function test_kambing_gets_kambing_mandiri_group(): void
    {
        $group = $this->service->assignGroup($this->period, 'kambing');
        $this->assertEquals('Kambing Mandiri', $group->name);
        $this->assertEquals('kambing', $group->target_type);
    }

    public function test_kambing_reuses_existing_group(): void
    {
        $g1 = $this->service->assignGroup($this->period, 'kambing');
        $g2 = $this->service->assignGroup($this->period, 'kambing');
        $this->assertEquals($g1->id, $g2->id);
    }

    public function test_sapi_creates_first_group(): void
    {
        $group = $this->service->assignGroup($this->period, 'sapi');
        $this->assertEquals('Sapi 1', $group->name);
        $this->assertEquals('sapi', $group->target_type);
    }

    public function test_sapi_fills_existing_group_before_creating_new(): void
    {
        $group1 = $this->service->assignGroup($this->period, 'sapi');
        // Add 3 members
        for ($i = 0; $i < 3; $i++) {
            Shohibul::create([
                'period_id' => $this->period->id, 'animal_group_id' => $group1->id,
                'name' => "M{$i}", 'phone' => "08{$i}", 'address' => 'A',
                'target_type' => 'sapi', 'target_amount' => 4000000,
            ]);
        }
        // Should still return Sapi 1 (only 3/7)
        $assigned = $this->service->assignGroup($this->period, 'sapi');
        $this->assertEquals($group1->id, $assigned->id);
    }

    public function test_sapi_creates_new_group_when_full(): void
    {
        $group1 = AnimalGroup::create([
            'period_id' => $this->period->id, 'name' => 'Sapi 1', 'target_type' => 'sapi',
        ]);
        for ($i = 1; $i <= 7; $i++) {
            Shohibul::create([
                'period_id' => $this->period->id, 'animal_group_id' => $group1->id,
                'name' => "M{$i}", 'phone' => "08{$i}", 'address' => 'A',
                'target_type' => 'sapi', 'target_amount' => 4000000,
            ]);
        }

        $newGroup = $this->service->assignGroup($this->period, 'sapi');
        $this->assertEquals('Sapi 2', $newGroup->name);
        $this->assertNotEquals($group1->id, $newGroup->id);
    }
}
