<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RbacSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'app.user.view',
        'app.user.manage',
        'app.role.view',
        'app.role.manage',
        'district.district.view',
        'district.district.manage',
        'district.customer.view',
        'district.customer.create',
        'district.customer.update',
        'district.customer.delete',
        'district.debt.view',
        'district.debt.create',
        'district.debt.update',
        'district.debt.delete',
        'district.payment.view',
        'district.payment.create',
        'district.payment.update',
        'district.payment.delete',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $appAdminRole = Role::query()->updateOrCreate(
            [
                'name' => 'app.admin',
                'guard_name' => 'web',
            ],
            [
                'scope' => 'app',
            ],
        );

        Role::query()->updateOrCreate(
            [
                'name' => 'district.admin',
                'guard_name' => 'web',
            ],
            [
                'scope' => 'district',
            ],
        )->syncPermissions([
            'district.district.view',
            'district.district.manage',
            'district.customer.view',
            'district.customer.create',
            'district.customer.update',
            'district.customer.delete',
            'district.debt.view',
            'district.debt.create',
            'district.debt.update',
            'district.debt.delete',
            'district.payment.view',
            'district.payment.create',
            'district.payment.update',
            'district.payment.delete',
        ]);

        Role::query()->updateOrCreate(
            [
                'name' => 'district.manager',
                'guard_name' => 'web',
            ],
            [
                'scope' => 'district',
            ],
        )->syncPermissions([
            'district.district.view',
            'district.customer.view',
            'district.customer.create',
            'district.customer.update',
            'district.debt.view',
            'district.debt.create',
            'district.debt.update',
            'district.payment.view',
            'district.payment.create',
            'district.payment.update',
        ]);

        Role::query()->updateOrCreate(
            [
                'name' => 'district.user',
                'guard_name' => 'web',
            ],
            [
                'scope' => 'district',
            ],
        )->syncPermissions([
            'district.district.view',
            'district.customer.view',
            'district.debt.view',
            'district.payment.view',
        ]);

        $appAdminRole->syncPermissions(Permission::query()->pluck('name')->all());
    }
}
