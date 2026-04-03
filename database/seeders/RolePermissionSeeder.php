<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage-users',
            'manage-products',
            'manage-stock',
            'manage-batches',
            'manage-expiration',
            'manage-suppliers',
            'use-pos',
            'manage-sales',
            'process-refunds',
            'manage-expenses',
            'view-financial-reports',
            'view-profits-losses',
            'view-dashboard',
            'manage-settings',
        ];

        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::query()->firstOrCreate(['name' => 'SUPER ADMIN', 'guard_name' => 'web']);
        $inventoryManager = Role::query()->firstOrCreate(['name' => 'INVENTORY MANAGER', 'guard_name' => 'web']);
        $cashier = Role::query()->firstOrCreate(['name' => 'CASHIER', 'guard_name' => 'web']);
        $accounts = Role::query()->firstOrCreate(['name' => 'ACCOUNTS', 'guard_name' => 'web']);

        // منح كل الصلاحيات للمدير الأعلى لضمان تشغيل النظام بالكامل.
        $superAdmin->permissions()->sync(Permission::query()->pluck('id'));
        $inventoryManager->permissions()->sync(Permission::query()->whereIn('name', [
            'manage-products', 'manage-stock', 'manage-batches', 'manage-expiration', 'manage-suppliers',
        ])->pluck('id'));
        $cashier->permissions()->sync(Permission::query()->whereIn('name', [
            'use-pos', 'manage-sales', 'process-refunds',
        ])->pluck('id'));
        $accounts->permissions()->sync(Permission::query()->whereIn('name', [
            'manage-expenses', 'view-financial-reports', 'view-profits-losses',
        ])->pluck('id'));

        $superAdminUser = User::query()->firstOrCreate(
            ['email' => 'super.admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'phone' => null,
                'is_active' => true,
                'password' => Hash::make('SuperAdmin@12345'),
            ]
        );

        $superAdminUser->roles()->syncWithoutDetaching([$superAdmin->id]);
    }
}
