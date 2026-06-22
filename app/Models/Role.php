<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public const AVAILABLE_MODULES = ['web', 'keuangan', 'qurban', 'sistem'];

    protected $casts = [
        'hierarchy' => 'integer',
        'modules' => 'array',
    ];

    public static function normalizeName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalized = Str::of($name)->trim()->lower()->replace('-', '_')->toString();

        return match ($normalized) {
            'superadmin', 'super_admin' => 'super-admin',
            default => Str::of($name)->trim()->lower()->replace(' ', '_')->toString(),
        };
    }

    public function frontendKey(): string
    {
        return $this->name === 'super-admin' ? 'superadmin' : $this->name;
    }

    public function displayName(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        return match ($this->name) {
            'super-admin' => 'Super Admin',
            default => Str::of($this->name)->replace(['-', '_'], ' ')->headline()->toString(),
        };
    }

    public function effectiveHierarchy(): int
    {
        if ($this->hierarchy !== null) {
            return $this->hierarchy;
        }

        return match ($this->name) {
            'super-admin' => 1,
            'admin' => 2,
            'bendahara' => 3,
            'sekretaris' => 4,
            'viewer' => 99,
            default => 50 + (int) $this->id,
        };
    }

    public function effectiveModules(): array
    {
        if (is_array($this->modules)) {
            return array_values(array_intersect(self::AVAILABLE_MODULES, $this->modules));
        }

        if ($this->name === 'super-admin' || $this->name === 'admin') {
            return self::AVAILABLE_MODULES;
        }

        if ($this->name === 'bendahara') {
            return ['keuangan'];
        }

        if ($this->name === 'sekretaris') {
            return ['web'];
        }

        return $this->modulesFromPermissions();
    }

    public static function permissionsForModules(array $modules): array
    {
        $permissions = [];

        foreach (array_values(array_intersect(self::AVAILABLE_MODULES, $modules)) as $module) {
            $permissions = array_merge($permissions, match ($module) {
                'web' => [
                    'jamaah.view', 'jamaah.create', 'jamaah.update', 'jamaah.delete',
                    'profile.view', 'profile.create', 'profile.update', 'profile.delete',
                ],
                'keuangan' => [
                    'keuangan.view', 'keuangan.create', 'keuangan.update', 'keuangan.delete', 'keuangan.export',
                    'keuangan.category.view', 'keuangan.category.create', 'keuangan.category.update', 'keuangan.category.delete',
                    'keuangan.bank_kas.view', 'keuangan.bank_kas.create', 'keuangan.bank_kas.update', 'keuangan.bank_kas.delete',
                    'keuangan.transaksi.view', 'keuangan.transaksi.create', 'keuangan.transaksi.update',
                    'keuangan.transaksi.delete', 'keuangan.transaksi.approve',
                    'keuangan.laporan.view', 'keuangan.laporan.export',
                    'keuangan.rekonsiliasi.create',
                ],
                'qurban' => [
                    'kurban.view', 'kurban.create', 'kurban.update', 'kurban.delete',
                    'qurban.periode.view', 'qurban.periode.create', 'qurban.periode.update',
                    'qurban.shohibul.view', 'qurban.shohibul.create', 'qurban.shohibul.update', 'qurban.shohibul.delete',
                    'qurban.transaksi.view', 'qurban.transaksi.create', 'qurban.transaksi.cancel',
                    'qurban.kelompok.view', 'qurban.kelompok.create', 'qurban.kelompok.update',
                    'qurban.rollover.execute',
                ],
                'sistem' => [
                    'user.view', 'user.create', 'user.update', 'user.delete',
                ],
            });
        }

        return array_values(array_unique($permissions));
    }

    private function modulesFromPermissions(): array
    {
        $permissions = $this->permissions->pluck('name');
        $modules = [];

        if ($permissions->contains(fn (string $permission) => str_starts_with($permission, 'profile.') || str_starts_with($permission, 'jamaah.'))) {
            $modules[] = 'web';
        }

        if ($permissions->contains(fn (string $permission) => str_starts_with($permission, 'keuangan.'))) {
            $modules[] = 'keuangan';
        }

        if ($permissions->contains(fn (string $permission) => str_starts_with($permission, 'qurban.') || str_starts_with($permission, 'kurban.'))) {
            $modules[] = 'qurban';
        }

        if ($permissions->contains(fn (string $permission) => str_starts_with($permission, 'user.'))) {
            $modules[] = 'sistem';
        }

        return $modules;
    }
}
