<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_events_publicly(): void
    {
        Event::factory()->count(3)->create(['is_active' => true]);
        $response = $this->getJson('/v1/web-profile/events');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_event(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/v1/web-profile/events', [
            'title' => 'New Event',
            'image' => UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg'),
            'description' => 'Event description',
            'content' => 'Full event content',
            'date' => '2026-06-25',
            'time' => '08:00:00',
            'category' => 'Kajian',
            'type' => 'Kajian',
            'author' => 'Admin',
            'is_active' => true,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', ['title' => 'New Event']);
    }

    public function test_admin_can_update_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $response = $this->actingAs($user)->putJson('/v1/web-profile/events/'.$event->id, [
            'title' => 'Updated Event',
            'type' => 'Kajian',
            'date' => '2026-06-25',
            'time' => '08:00:00',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Event']);
    }

    public function test_admin_can_delete_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/v1/web-profile/events/'.$event->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
