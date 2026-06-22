<?php

namespace Tests\Feature\User;

use App\Models\Role;
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
                        '*' => ['id', 'name', 'username', 'email', 'role', 'role_data', 'roles'],
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
            'username' => 'new_user',
            'email' => 'new@example.com',
            'password' => 'password123',
            'role' => 'viewer',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New User',
                    'username' => 'new_user',
                    'email' => 'new@example.com',
                    'role' => 'viewer',
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
            'role' => 'superadmin',
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
            'username' => 'updated_user',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'username' => 'updated_user',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Updated Name',
            'username' => 'updated_user',
        ]);
    }

    public function test_admin_can_create_user_with_username_without_email(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/v1/users', [
            'name' => 'Bendahara Masjid',
            'username' => 'bendahara_baru',
            'password' => 'password123',
            'role' => 'bendahara',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'username' => 'bendahara_baru',
                    'email' => 'bendahara_baru@local.dkm',
                    'role' => 'bendahara',
                ],
            ]);

        $user = User::where('username', 'bendahara_baru')->first();
        $this->assertTrue($user->hasRole('bendahara'));
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

    public function test_admin_can_list_roles_with_frontend_metadata(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/v1/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'key', 'name', 'hierarchy', 'modules', 'permissions'],
                ],
            ])
            ->assertJsonFragment([
                'key' => 'superadmin',
                'name' => 'Super Admin',
                'hierarchy' => 1,
            ])
            ->assertJsonFragment([
                'key' => 'bendahara',
                'name' => 'Bendahara',
                'modules' => ['keuangan'],
            ]);
    }

    public function test_admin_can_create_update_move_and_delete_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/v1/roles', [
            'key' => 'humas',
            'name' => 'Humas',
            'modules' => ['web'],
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.key', 'humas')
            ->assertJsonPath('data.name', 'Humas')
            ->assertJsonPath('data.modules', ['web']);

        $roleId = $createResponse->json('data.id');

        $this->putJson("/v1/roles/{$roleId}", [
            'name' => 'Humas dan Publikasi',
            'modules' => ['web', 'sistem'],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Humas dan Publikasi')
            ->assertJsonPath('data.modules', ['web', 'sistem']);

        $this->patchJson("/v1/roles/{$roleId}/move", [
            'direction' => 'up',
        ])->assertOk();

        $this->deleteJson("/v1/roles/{$roleId}")
            ->assertOk();
    }

    public function test_cannot_delete_role_that_is_assigned_to_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        $user = User::factory()->create();
        $user->assignRole('bendahara');

        $role = Role::where('name', 'bendahara')->first();

        $this->deleteJson("/v1/roles/{$role->id}")
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Role is still assigned to users.',
            ]);
    }
}
