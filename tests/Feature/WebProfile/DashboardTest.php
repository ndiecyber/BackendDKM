<?php

namespace Tests\Feature\WebProfile;

use App\Models\WebProfile\CommitteeMember;
use App\Models\WebProfile\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_dashboard_stats(): void
    {
        Service::factory()->count(2)->create();
        CommitteeMember::factory()->count(3)->create();

        $response = $this->getJson('/v1/web-profile/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_services',
                    'total_committee_members',
                ],
            ]);
    }
}
