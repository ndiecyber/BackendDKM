<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\CtaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CtaSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_cta_settings(): void
    {
        CtaSetting::factory()->create();
        $response = $this->getJson('/v1/web-profile/cta');
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'programs']]);
    }

    public function test_admin_can_update_cta_settings(): void
    {
        $user = User::factory()->create();
        $setting = CtaSetting::factory()->create();

        $response = $this->actingAs($user)->putJson('/v1/web-profile/cta', [
            'title' => 'New CTA Title',
            'programs' => [
                ['name' => 'Program 1', 'progress' => 50],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cta_settings', ['title' => 'New CTA Title']);
        $this->assertDatabaseHas('cta_programs', ['name' => 'Program 1', 'progress' => 50]);
    }
}
