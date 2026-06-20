<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\CommitteeMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommitteeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_committee_structure(): void
    {
        CommitteeMember::factory()->create(['group' => 'dewan_penasihat']);
        $response = $this->getJson('/v1/web-profile/committee');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'dewanPenasihat',
                    'pengurusHarian',
                    'divisi',
                ],
            ]);
    }

    public function test_admin_can_update_committee_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/v1/web-profile/committee', [
            'dewanPenasihat' => [
                ['name' => 'Penasihat 1', 'role' => 'Ketua'],
            ],
            'pengurusHarian' => [],
            'divisi' => [
                [
                    'name' => 'Divisi IT',
                    'members' => [
                        ['name' => 'Member IT 1', 'role' => 'Staff'],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('committee_members', ['name' => 'Penasihat 1']);
        $this->assertDatabaseHas('committee_divisions', ['name' => 'Divisi IT']);
        $this->assertDatabaseHas('committee_members', ['name' => 'Member IT 1']);
    }
}
