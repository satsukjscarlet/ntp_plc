<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Người dùng
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign-role',
            'users.activate',

            // Vai trò và quyền
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            // Danh mục thiết bị
            'plants.view',
            'plants.create',
            'plants.update',
            'plants.delete',

            'production-lines.view',
            'production-lines.create',
            'production-lines.update',
            'production-lines.delete',

            'machines.view',
            'machines.create',
            'machines.update',
            'machines.delete',

            'plcs.view',
            'plcs.create',
            'plcs.update',
            'plcs.delete',

            'plc-tags.view',
            'plc-tags.create',
            'plc-tags.update',
            'plc-tags.delete',
            'plc-tags.configure-oee',

            // Thu thập dữ liệu
            'collector.view',
            'collector.manage',
            'collector.retry',

            // Giám sát
            'monitoring.view',
            'monitoring.trend',
            'monitoring.export',

            // Sản xuất
            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            'production-shifts.view',
            'production-shifts.create',
            'production-shifts.update',
            'production-shifts.delete',

            'machine-product-standards.view',
            'machine-product-standards.create',
            'machine-product-standards.update',
            'machine-product-standards.delete',


            'downtime-reasons.view',
            'downtime-reasons.create',
            'downtime-reasons.update',
            'downtime-reasons.delete',

            'production-declarations.view',
            'production-declarations.create',
            'production-declarations.update',
            'production-declarations.approve',
            'production-declarations.lock',
            'production-declarations.unlock',
            'production-declarations.delete',
            'production-declarations.restore',

            // OEE
            'oee.view',
            'oee.calculate',
            'oee.recalculate',
            'oee.export',

            // Báo cáo
            'reports.view',
            'reports.export',

            // Nhật ký
            'activity-logs.view',

            // Cấu hình hệ thống
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'SUPER_ADMIN' => $permissions,

            'ADMIN' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.update',
                'users.assign-role', 'users.activate',
                'roles.view',
                'plants.view', 'plants.create', 'plants.update',
                'production-lines.view', 'production-lines.create', 'production-lines.update',
                'machines.view', 'machines.create', 'machines.update',
                'plcs.view', 'plcs.create', 'plcs.update',
                'plc-tags.view', 'plc-tags.create', 'plc-tags.update',
                'plc-tags.configure-oee',
                'collector.view', 'collector.manage', 'collector.retry',
                'monitoring.view', 'monitoring.trend', 'monitoring.export',
                'products.view', 'products.create', 'products.update',
                'production-shifts.view', 'production-shifts.create', 'production-shifts.update',
                'machine-product-standards.view',
                'machine-product-standards.create',
                'machine-product-standards.update',
                'downtime-reasons.view', 'downtime-reasons.create', 'downtime-reasons.update',
                'production-declarations.view',
                'production-declarations.create',
                'production-declarations.update',
                'production-declarations.approve',
                'production-declarations.lock',
                'production-declarations.unlock',
                'production-declarations.delete',
                'production-declarations.restore',
                'oee.view', 'oee.calculate', 'oee.recalculate', 'oee.export',
                'reports.view', 'reports.export',
                'activity-logs.view',
                'settings.view', 'settings.update',
            ],

            'BAN_CNTT' => [
                'dashboard.view',
                'plants.view', 'plants.create', 'plants.update',
                'production-lines.view', 'production-lines.create', 'production-lines.update',
                'machines.view', 'machines.create', 'machines.update',
                'plcs.view', 'plcs.create', 'plcs.update',
                'plc-tags.view', 'plc-tags.create', 'plc-tags.update',
                'plc-tags.configure-oee',
                'collector.view', 'collector.manage', 'collector.retry',
                'monitoring.view', 'monitoring.trend', 'monitoring.export',
                'oee.view', 'oee.calculate', 'oee.recalculate',
                'reports.view', 'reports.export',
                'activity-logs.view',
                'settings.view', 'settings.update',
            ],

            'QUAN_DOC' => [
                'dashboard.view',
                'plants.view', 'production-lines.view', 'machines.view',
                'plcs.view', 'plc-tags.view',
                'collector.view',
                'monitoring.view', 'monitoring.trend', 'monitoring.export',
                'products.view',
                'production-declarations.view',
                'production-declarations.approve',
                'production-declarations.lock',
                'oee.view', 'oee.calculate', 'oee.recalculate', 'oee.export',
                'reports.view', 'reports.export',
            ],

            'TO_TRUONG' => [
                'dashboard.view',
                'machines.view',
                'monitoring.view', 'monitoring.trend',
                'products.view',
                'production-declarations.view',
                'production-declarations.create',
                'production-declarations.update',
                'oee.view',
                'reports.view',
            ],

            'KCS' => [
                'dashboard.view',
                'machines.view',
                'monitoring.view', 'monitoring.trend',
                'products.view',
                'production-declarations.view',
                'oee.view',
                'reports.view', 'reports.export',
            ],

            'VAN_HANH' => [
                'dashboard.view',
                'machines.view',
                'monitoring.view',
                'products.view',
                'production-declarations.view',
                'production-declarations.create',
                'production-declarations.update',
            ],

            'XEM_BAO_CAO' => [
                'dashboard.view',
                'monitoring.view',
                'oee.view',
                'reports.view',
                'reports.export',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}