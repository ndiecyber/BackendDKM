<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\Gallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_galleries_publicly(): void
    {
        Gallery::factory()->count(3)->create(['is_active' => true]);
        $response = $this->getJson('/v1/web-profile/galleries');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_gallery(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/v1/web-profile/galleries', [
            'caption' => 'Caption here',
            'image' => UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg'),
            'subcaption' => 'Subcaption',
            'category' => 'Kegiatan',
            'is_active' => true,
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('galleries', ['caption' => 'Caption here']);
    }

    public function test_admin_can_update_gallery(): void
    {
        $user = User::factory()->create();
        $gallery = Gallery::factory()->create();
        $response = $this->actingAs($user)->putJson('/v1/web-profile/galleries/'.$gallery->id, [
            'caption' => 'Updated Caption',
            'category' => 'Kegiatan',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('galleries', ['id' => $gallery->id, 'caption' => 'Updated Caption']);
    }

    public function test_admin_can_delete_gallery(): void
    {
        $user = User::factory()->create();
        $gallery = Gallery::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/v1/web-profile/galleries/'.$gallery->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
    }
}
