<?php

namespace Tests\Feature\Jamaah;

use App\Models\Jamaah;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JamaahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_list_jamaah(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        Jamaah::create([
            'nama_lengkap' => 'Budi',
            'no_hp' => '081234567890',
            'email' => 'budi@example.com',
            'jenis_kelamin' => 'L',
        ]);

        $response = $this->getJson('/api/v1/jamaah');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'nama_lengkap', 'no_hp', 'email', 'jenis_kelamin', 'status'],
                    ],
                    'current_page',
                    'total',
                ],
            ]);
    }

    public function test_viewer_can_list_jamaah(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/jamaah');

        $response->assertOk();
    }

    public function test_viewer_cannot_create_jamaah(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $response = $this->postJson('/api/v1/jamaah', [
            'nama_lengkap' => 'Siti',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_jamaah(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/jamaah', [
            'nama_lengkap' => 'Ahmad',
            'no_hp' => '08111222333',
            'email' => 'ahmad@example.com',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_lengkap' => 'Ahmad',
                    'no_hp' => '08111222333',
                ],
            ]);

        $this->assertDatabaseHas('jamaah', ['email' => 'ahmad@example.com']);
    }

    public function test_admin_can_update_jamaah(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $jamaah = Jamaah::create([
            'nama_lengkap' => 'Old Name',
        ]);

        $response = $this->putJson("/api/v1/jamaah/{$jamaah->id}", [
            'nama_lengkap' => 'Updated Name',
            'status' => 'nonaktif',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_lengkap' => 'Updated Name',
                    'status' => 'nonaktif',
                ],
            ]);

        $this->assertDatabaseHas('jamaah', [
            'id' => $jamaah->id,
            'nama_lengkap' => 'Updated Name',
            'status' => 'nonaktif',
        ]);
    }

    public function test_admin_can_soft_delete_jamaah(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $jamaah = Jamaah::create([
            'nama_lengkap' => 'To Be Deleted',
        ]);

        $response = $this->deleteJson("/api/v1/jamaah/{$jamaah->id}");

        $response->assertOk();
        $this->assertSoftDeleted('jamaah', ['id' => $jamaah->id]);
    }

    public function test_admin_can_restore_soft_deleted_jamaah(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $jamaah = Jamaah::create([
            'nama_lengkap' => 'To Be Restored',
        ]);
        $jamaah->delete();

        $this->assertSoftDeleted('jamaah', ['id' => $jamaah->id]);

        $response = $this->patchJson("/api/v1/jamaah/{$jamaah->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('jamaah', [
            'id' => $jamaah->id,
            'deleted_at' => null,
        ]);
    }
}
