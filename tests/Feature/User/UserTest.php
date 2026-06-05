<?php

namespace Tests\Feature\User;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        User::factory()->count(5)->create();

        $response = $this->getJson('/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'roles'],
                    ],
                    'current_page',
                    'total',
                ],
            ]);
    }

    public function test_viewer_cannot_create_user(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');
        Sanctum::actingAs($viewer);

        $response = $this->postJson('/v1/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/v1/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'viewer',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New User',
                    'email' => 'new@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $user = User::where('email', 'new@example.com')->first();
        $this->assertTrue($user->hasRole('viewer'));
    }

    public function test_admin_cannot_assign_super_admin_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/v1/users', [
            'name' => 'Super User',
            'email' => 'super@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super-admin',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $userToUpdate = User::factory()->create();
        $userToUpdate->assignRole('viewer');

        $response = $this->putJson("/v1/users/{$userToUpdate->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/v1/users/{$admin->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You cannot delete yourself.',
            ]);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $userToDelete = User::factory()->create();

        $response = $this->deleteJson("/v1/users/{$userToDelete->id}");

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $userToDelete->id]);
    }

    public function test_admin_can_restore_soft_deleted_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $userToRestore = User::factory()->create();
        $userToRestore->delete();

        $this->assertSoftDeleted('users', ['id' => $userToRestore->id]);

        $response = $this->patchJson("/v1/users/{$userToRestore->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $userToRestore->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_reset_password(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $userToReset = User::factory()->create();

        $response = $this->patchJson("/v1/users/{$userToReset->id}/reset-password");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['new_password'],
            ]);
    }
}
