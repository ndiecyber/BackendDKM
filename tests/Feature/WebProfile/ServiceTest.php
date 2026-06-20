<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_services_publicly(): void
    {
        Service::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/v1/web-profile/services');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_service(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/v1/web-profile/services', [
            'title' => 'New Service',
            'bg_image' => UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg'),
            'description' => 'Description here',
            'icon' => 'fa-star',
            'category' => 'Ibadah',
            'badge' => 'Baru',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('services', ['title' => 'New Service']);
    }

    public function test_admin_can_update_service(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($user)->putJson('/v1/web-profile/services/'.$service->id, [
            'title' => 'Updated Service',
            'category' => 'Ibadah',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('services', ['id' => $service->id, 'title' => 'Updated Service']);
    }

    public function test_admin_can_delete_service(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/v1/web-profile/services/'.$service->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }
}
