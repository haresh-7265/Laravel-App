<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

        // permissions — guard specific
        $permissions = [
            // products
            ['name' => 'manage_products', 'group' => 'products'],
            ['name' => 'view_products',   'group' => 'products'],

            // orders
            ['name' => 'manage_orders',   'group' => 'orders'],
            ['name' => 'refund_orders',   'group' => 'orders'],

            // reports
            ['name' => 'view_reports',    'group' => 'reports'],

            // users
            ['name' => 'manage_users',    'group' => 'users'],
            ['name' => 'assign_roles',    'group' => 'users'],

            ['name' => 'manage_cache',    'group' => 'cache'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name'], 'guard_name' => 'web'],
                // ['group' => $p['group'] ?? null]
            );
        }

        // admin guard permissions — same set
        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p['name'], 'guard_name' => 'admin'],
            );
        }

        // web roles
        $webRoles = [
            'customer' => ['view_products'],
            'support'  => ['view_products', 'manage_orders', 'refund_orders'],
            'manager'  => ['manage_products', 'manage_orders', 'view_reports', 'refund_orders'],
        ];

        foreach ($webRoles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions(
                Permission::whereIn('name', $perms)->where('guard_name', 'web')->get()
            );
        }

        // admin role — all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $adminRole->syncPermissions(
            Permission::where('guard_name', 'admin')->get()
        );
    }
}