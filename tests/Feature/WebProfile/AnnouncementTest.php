<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_announcements_publicly(): void
    {
        Announcement::factory()->count(3)->create();
        $response = $this->getJson('/v1/web-profile/announcements');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_announcement(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/v1/web-profile/announcements', [
            'title' => 'New Announcement',
            'content' => 'Announcement content',
            'is_active' => true,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('announcements', ['title' => 'New Announcement']);
    }

    public function test_admin_can_update_announcement(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create();
        $response = $this->actingAs($user)->putJson('/v1/web-profile/announcements/'.$announcement->id, [
            'title' => 'Updated Announcement',
            'is_active' => false,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'title' => 'Updated Announcement']);
    }

    public function test_admin_can_delete_announcement(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/v1/web-profile/announcements/'.$announcement->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }
}
