<?php

namespace Tests\Feature\Keuangan;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_list_categories(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        Category::create(['nama' => 'Infaq', 'tipe' => 'pemasukan']);

        $response = $this->getJson('/v1/keuangan/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'nama', 'tipe', 'status', 'visibilitas'],
                    ],
                ],
            ]);
    }

    public function test_viewer_can_list_categories(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $response = $this->getJson('/v1/keuangan/categories');

        $response->assertOk();
    }

    public function test_viewer_cannot_create_category(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $response = $this->postJson('/v1/keuangan/categories', [
            'nama' => 'Test',
            'tipe' => 'pemasukan',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/v1/keuangan/categories', [
            'nama' => 'Zakat Fitrah',
            'tipe' => 'pemasukan',
            'deskripsi' => 'Zakat fitrah dari jamaah',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama' => 'Zakat Fitrah',
                    'tipe' => 'pemasukan',
                ],
            ]);

        $this->assertDatabaseHas('categories', ['nama' => 'Zakat Fitrah']);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $category = Category::create(['nama' => 'Old', 'tipe' => 'pemasukan']);

        $response = $this->putJson("/v1/keuangan/categories/{$category->id}", [
            'nama' => 'Updated',
            'status' => 'non_aktif',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['nama' => 'Updated', 'status' => 'non_aktif'],
            ]);
    }

    public function test_admin_can_soft_delete_and_restore_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $category = Category::create(['nama' => 'To Delete', 'tipe' => 'pengeluaran']);

        // Delete
        $response = $this->deleteJson("/v1/keuangan/categories/{$category->id}");
        $response->assertOk();
        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        // Restore
        $response = $this->patchJson("/v1/keuangan/categories/{$category->id}/restore");
        $response->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_can_filter_categories_by_tipe(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        Category::create(['nama' => 'Infaq', 'tipe' => 'pemasukan']);
        Category::create(['nama' => 'Operasional', 'tipe' => 'pengeluaran']);

        $response = $this->getJson('/v1/keuangan/categories?tipe=pemasukan');

        $response->assertOk();
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('pemasukan', $data[0]['tipe']);
    }
}
