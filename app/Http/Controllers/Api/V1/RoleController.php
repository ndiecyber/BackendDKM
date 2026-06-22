<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

#[Group('Manajemen Role')]
class RoleController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        Gate::authorize('user.view');

        $roles = Role::query()
            ->with('permissions:id,name')
            ->get()
            ->sortBy(fn (Role $role) => $role->effectiveHierarchy())
            ->values()
            ->map(fn (Role $role) => $this->roleResource($role));

        return $this->successResponse($roles);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('user.create');

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'modules' => ['array'],
            'modules.*' => [Rule::in(Role::AVAILABLE_MODULES)],
        ]);

        $roleName = Role::normalizeName($validated['key']);

        if (Role::where('name', $roleName)->exists()) {
            return $this->errorResponse('Role key already exists.', 422);
        }

        $role = Role::create([
            'name' => $roleName,
            'display_name' => $validated['name'],
            'guard_name' => 'web',
            'hierarchy' => $this->nextHierarchy(),
            'modules' => $validated['modules'] ?? [],
        ]);

        $this->syncModulePermissions($role, $role->effectiveModules());

        return $this->createdResponse($this->roleResource($role->refresh()->load('permissions:id,name')), 'Role created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        Gate::authorize('user.view');

        $role = Role::with('permissions:id,name')->findOrFail($id);

        return $this->successResponse($this->roleResource($role));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        Gate::authorize('user.update');

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'modules' => ['sometimes', 'array'],
            'modules.*' => [Rule::in(Role::AVAILABLE_MODULES)],
        ]);

        if (array_key_exists('name', $validated)) {
            $role->display_name = $validated['name'];
        }

        if (array_key_exists('modules', $validated)) {
            $role->modules = $validated['modules'];

            if ($role->name !== 'super-admin') {
                $this->syncModulePermissions($role, $validated['modules']);
            }
        }

        $role->save();

        return $this->successResponse($this->roleResource($role->refresh()->load('permissions:id,name')), 'Role updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('user.delete');

        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return $this->errorResponse('Super Admin role cannot be deleted.', 400);
        }

        if ($this->assignedUsersCount($role) > 0) {
            return $this->errorResponse('Role is still assigned to users.', 400);
        }

        $role->delete();
        $this->recalculateHierarchy();

        return $this->successResponse(null, 'Role deleted successfully.');
    }

    public function move(Request $request, string $id): JsonResponse
    {
        Gate::authorize('user.update');

        $validated = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $roles = Role::query()
            ->orderByRaw('COALESCE(hierarchy, id + 50)')
            ->get()
            ->values();

        $index = $roles->search(fn (Role $role) => (string) $role->id === (string) $id);

        if ($index === false) {
            abort(404);
        }

        $role = $roles[$index];

        if ($role->name === 'super-admin') {
            return $this->errorResponse('Super Admin role hierarchy cannot be changed.', 400);
        }

        $targetIndex = $validated['direction'] === 'up' ? $index - 1 : $index + 1;

        if (! isset($roles[$targetIndex]) || $roles[$targetIndex]->name === 'super-admin') {
            return $this->successResponse($this->roleResource($role->load('permissions:id,name')), 'Role hierarchy unchanged.');
        }

        [$roles[$index], $roles[$targetIndex]] = [$roles[$targetIndex], $roles[$index]];

        foreach ($roles->values() as $position => $orderedRole) {
            $orderedRole->forceFill(['hierarchy' => $position + 1])->save();
        }

        return $this->successResponse(
            Role::with('permissions:id,name')
                ->get()
                ->sortBy(fn (Role $role) => $role->effectiveHierarchy())
                ->values()
                ->map(fn (Role $role) => $this->roleResource($role)),
            'Role hierarchy updated successfully.'
        );
    }

    private function roleResource(Role $role): array
    {
        return [
            'id' => $role->id,
            'key' => $role->frontendKey(),
            'name' => $role->displayName(),
            'hierarchy' => $role->effectiveHierarchy(),
            'modules' => $role->effectiveModules(),
            'permissions' => $role->permissions->pluck('name')->values(),
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
        ];
    }

    private function nextHierarchy(): int
    {
        return ((int) Role::query()->max('hierarchy')) + 1;
    }

    private function recalculateHierarchy(): void
    {
        Role::query()
            ->orderByRaw('COALESCE(hierarchy, id + 50)')
            ->get()
            ->values()
            ->each(fn (Role $role, int $index) => $role->forceFill(['hierarchy' => $index + 1])->save());
    }

    private function syncModulePermissions(Role $role, array $modules): void
    {
        $permissionNames = Role::permissionsForModules($modules);

        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('name')
            ->all();

        $role->syncPermissions($permissions);
    }

    private function assignedUsersCount(Role $role): int
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->where(config('permission.column_names.role_pivot_key') ?? 'role_id', $role->id)
            ->count();
    }
}
