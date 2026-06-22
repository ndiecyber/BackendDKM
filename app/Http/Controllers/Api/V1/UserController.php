<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Group('Manajemen Pengguna')]
class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('user.view');

        $users = User::query()
            ->search($request->search)
            ->when($request->role, function ($query, $role) {
                $query->role(Role::normalizeName($role));
            })
            ->with('roles:id,name,display_name,hierarchy,modules')
            ->paginate($request->per_page ?? 15)
            ->through(fn (User $user) => $this->userResource($user));

        return $this->successResponse($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Only super-admin can assign super-admin role
        if ($request->role === 'super-admin' && ! $request->user()->hasRole('super-admin')) {
            return $this->errorResponse('Unauthorized to assign super-admin role.', 403);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email ?? $this->localEmailFromUsername($request->username),
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        $user->load('roles:id,name,display_name,hierarchy,modules');

        return $this->createdResponse($this->userResource($user), 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('user.view');

        $user = User::with('roles:id,name,display_name,hierarchy,modules')->findOrFail($id);

        return $this->successResponse([
            'user' => $this->userResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($request->has('role')) {
            // Cannot change super-admin role unless you are super-admin
            if ($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin')) {
                return $this->errorResponse('Unauthorized to modify super-admin role.', 403);
            }

            // Cannot assign super-admin role unless you are super-admin
            if ($request->role === 'super-admin' && ! $request->user()->hasRole('super-admin')) {
                return $this->errorResponse('Unauthorized to assign super-admin role.', 403);
            }

            $user->syncRoles([$request->role]);
        }

        $data = $request->only(['name', 'username', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $user->load('roles:id,name,display_name,hierarchy,modules');

        return $this->successResponse($this->userResource($user), 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        Gate::authorize('user.delete');

        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return $this->errorResponse('You cannot delete yourself.', 400);
        }

        if ($user->hasRole('super-admin')) {
            $superAdminsCount = User::role('super-admin')->count();
            if ($superAdminsCount <= 1) {
                return $this->errorResponse('Cannot delete the last super-admin.', 400);
            }
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(Request $request, string $id): JsonResponse
    {
        Gate::authorize('user.delete'); // using delete permission for restore

        $user = User::withTrashed()->findOrFail($id);

        if (! $user->trashed()) {
            return $this->errorResponse('User is not deleted.', 400);
        }

        $user->restore();

        return $this->successResponse(null, 'User restored successfully.');
    }

    /**
     * Reset user password to a random temporary string.
     */
    public function resetPassword(Request $request, string $id): JsonResponse
    {
        Gate::authorize('user.update');

        $user = User::findOrFail($id);

        // Cannot reset super-admin password unless you are super-admin
        if ($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin')) {
            return $this->errorResponse('Unauthorized to reset super-admin password.', 403);
        }

        $newPassword = Str::random(10);

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return $this->successResponse([
            'new_password' => $newPassword,
        ], 'Password reset successfully.');
    }

    private function userResource(User $user): array
    {
        /** @var Role|null $role */
        $role = $user->roles->sortBy(fn (Role $role) => $role->effectiveHierarchy())->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username ?? Str::before($user->email, '@'),
            'email' => $user->email,
            'role' => $role?->frontendKey(),
            'role_data' => $role ? [
                'id' => $role->id,
                'key' => $role->frontendKey(),
                'name' => $role->displayName(),
                'hierarchy' => $role->effectiveHierarchy(),
                'modules' => $role->effectiveModules(),
            ] : null,
            'roles' => $user->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'key' => $role->frontendKey(),
                'name' => $role->displayName(),
                'guard_name' => $role->guard_name,
            ])->values(),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function localEmailFromUsername(string $username): string
    {
        return Str::of($username)
            ->lower()
            ->replaceMatches('/[^a-z0-9_.-]/', '')
            ->append('@local.dkm')
            ->toString();
    }
}
