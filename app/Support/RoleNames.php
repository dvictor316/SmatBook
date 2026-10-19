<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RoleNames
{
    public static function key(?string $name): ?string
    {
        $name = strtolower(trim(preg_replace('/[\s_-]+/u', ' ', (string) $name)));
        if ($name === '') {
            return null;
        }

        return match ($name) {
            'super admin', 'super_admin', 'superadmin' => 'super_admin',
            'administrator', 'admin' => 'administrator',
            'state manager', 'state_manager', 'deployment manager', 'deployment_manager' => 'state_manager',
            'agent', 'sales agent', 'sales_agent' => 'agent',
            'account officer', 'account_officer', 'accountant' => 'accountant',
            default => Str::snake(str_replace('-', ' ', $name)),
        };
    }

    public static function aliases(string $name): array
    {
        $key = self::key($name);

        return array_values(array_unique(array_merge([
            strtolower(trim($name)), $key, str_replace('_', ' ', (string) $key),
        ], match ($key) {
            'super_admin' => ['superadmin'],
            'administrator' => ['admin'],
            'state_manager' => ['deployment_manager', 'deployment manager'],
            'agent' => ['sales_agent', 'sales agent'],
            'accountant' => ['account_officer', 'account officer'],
            default => [],
        })));
    }

    public static function find(?string $name): ?Role
    {
        if (! self::key($name) || ! Schema::hasTable('roles')) {
            return null;
        }

        return Role::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])->first()
            ?? Role::all()->first(fn (Role $role) => self::key($role->name) === self::key($name));
    }
}
