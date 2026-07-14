<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

#[Group('Autentikasi')]
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('viewer');

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->createdResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $this->primaryRole($user)?->frontendKey(),
                'roles' => $user->getRoleNames(),
            ],
            'token' => $token,
        ], 'User registered successfully');
    }

    /**
     * Login and generate API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = 'login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return $this->errorResponse(
                "Too many login attempts. Please try again in {$seconds} seconds.",
                429
            );
        }

        $login = strtolower(trim($request->input('login', $request->input('email', $request->input('username')))));

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$login])
            ->orWhereRaw('LOWER(username) = ?', [$login])
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return $this->errorResponse('Invalid credentials', 401);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $this->primaryRole($user)?->frontendKey(),
                'role_data' => $this->primaryRoleResource($user),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $this->primaryRole($user)?->frontendKey(),
            'role_data' => $this->primaryRoleResource($user),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Refresh — rotate token (revoke old, create new).
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $token = $request->user()->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
        ], 'Token refreshed successfully');
    }

    private function primaryRole(User $user): ?Role
    {
        /** @var Role|null $role */
        $role = $user->roles->sortBy(fn (Role $role) => $role->effectiveHierarchy())->first();

        return $role;
    }

    private function primaryRoleResource(User $user): ?array
    {
        $role = $this->primaryRole($user);

        if (! $role) {
            return null;
        }

        return [
            'id' => $role->id,
            'key' => $role->frontendKey(),
            'name' => $role->displayName(),
            'hierarchy' => $role->effectiveHierarchy(),
            'modules' => $role->effectiveModules(),
        ];
    }
}
