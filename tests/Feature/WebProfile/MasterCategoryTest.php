<?php

namespace Tests\Feature\WebProfile;

use App\Models\User;
use App\Models\WebProfile\MasterCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_master_categories_publicly(): void
    {
        MasterCategory::factory()->count(3)->create(['type' => 'kategori']);
        $response = $this->getJson('/v1/web-profile/master-categories');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_master_category(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/v1/web-profile/master-categories', [
            'type' => 'kategori',
            'name' => 'Kategori Baru',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('master_categories', ['name' => 'Kategori Baru']);
    }

    public function test_admin_can_update_master_category(): void
    {
        $user = User::factory()->create();
        $category = MasterCategory::factory()->create();
        $response = $this->actingAs($user)->putJson('/v1/web-profile/master-categories/'.$category->id, [
            'type' => 'kategori',
            'name' => 'Updated Kategori',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('master_categories', ['id' => $category->id, 'name' => 'Updated Kategori']);
    }

    public function test_admin_can_delete_master_category(): void
    {
        $user = User::factory()->create();
        $category = MasterCategory::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/v1/web-profile/master-categories/'.$category->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('master_categories', ['id' => $category->id]);
    }
}
