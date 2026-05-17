<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedAdminPermissions extends Seeder
{
    /**
     * Seed comprehensive admin permissions for every sidebar module.
     * Idempotent — skips permissions that already exist.
     */
    public function run()
    {
        $guard = 'admin';

        $permissions = [
            // ── Existing groups (will be skipped if already present) ──
            [
                'group_name' => 'dashboard',
                'permissions' => ['dashboard.view', 'dashboard.edit'],
            ],
            [
                'group_name' => 'admin',
                'permissions' => ['admin.create', 'admin.view', 'admin.edit', 'admin.delete'],
            ],
            [
                'group_name' => 'role',
                'permissions' => ['role.create', 'role.view', 'role.edit', 'role.delete'],
            ],
            [
                'group_name' => 'profile',
                'permissions' => ['profile.view', 'profile.edit'],
            ],
            [
                'group_name' => 'category',
                'permissions' => ['category.create', 'category.view', 'category.edit', 'category.delete'],
            ],

            // ── New groups ──
            [
                'group_name' => 'product',
                'permissions' => ['product.create', 'product.view', 'product.edit', 'product.delete'],
            ],
            [
                'group_name' => 'shop-product',
                'permissions' => ['shop-product.view', 'shop-product.edit'],
            ],
            [
                'group_name' => 'order',
                'permissions' => ['order.create', 'order.view', 'order.edit', 'order.delete'],
            ],
            [
                'group_name' => 'supplier',
                'permissions' => ['supplier.create', 'supplier.view', 'supplier.edit', 'supplier.delete', 'supplier.all', 'supplier.active', 'supplier.requests', 'supplier.products', 'supplier.reviews', 'supplier.discounts', 'supplier.commissions', 'supplier.payouts'],
            ],
            [
                'group_name' => 'user',
                'permissions' => ['user.create', 'user.view', 'user.edit', 'user.delete', 'user.all', 'user.manage', 'user.active'],
            ],
            [
                'group_name' => 'banner',
                'permissions' => ['banner.create', 'banner.view', 'banner.edit', 'banner.delete'],
            ],
            [
                'group_name' => 'flash-sale',
                'permissions' => ['flash-sale.create', 'flash-sale.view', 'flash-sale.edit', 'flash-sale.delete'],
            ],
            [
                'group_name' => 'attribute',
                'permissions' => ['attribute.create', 'attribute.view', 'attribute.edit', 'attribute.delete'],
            ],
            [
                'group_name' => 'setting',
                'permissions' => ['setting.view', 'setting.edit'],
            ],
            [
                'group_name' => 'sales-target',
                'permissions' => ['sales-target.create', 'sales-target.view', 'sales-target.edit', 'sales-target.delete'],
            ],
            [
                'group_name' => 'report',
                'permissions' => ['report.view'],
            ],
            [
                'group_name' => 'notification',
                'permissions' => ['notification.create', 'notification.view'],
            ],
            [
                'group_name' => 'ticket',
                'permissions' => ['ticket.view', 'ticket.edit'],
            ],
            [
                'group_name' => 'fraud',
                'permissions' => ['fraud.view', 'fraud.edit'],
            ],
            [
                'group_name' => 'content',
                'permissions' => ['content.create', 'content.view', 'content.edit', 'content.delete'],
            ],
            [
                'group_name' => 'announcement',
                'permissions' => ['announcement.create', 'announcement.view', 'announcement.edit', 'announcement.delete'],
            ],
            [
                'group_name' => 'marketing',
                'permissions' => ['marketing.create', 'marketing.view', 'marketing.edit', 'marketing.delete'],
            ],
            [
                'group_name' => 'course',
                'permissions' => ['course.create', 'course.view', 'course.edit', 'course.delete'],
            ],
            [
                'group_name' => 'crm',
                'permissions' => ['crm.view'],
            ],
            [
                'group_name' => 'others',
                'permissions' => ['others.view', 'others.edit'],
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($permissions as $group) {
            foreach ($group['permissions'] as $permName) {
                $exists = Permission::where('name', $permName)
                    ->where('guard_name', $guard)
                    ->first();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Permission::create([
                    'name'       => $permName,
                    'group_name' => $group['group_name'],
                    'guard_name' => $guard,
                ]);
                $created++;
            }
        }

        // Give Superadmin role ALL permissions
        $superadmin = Role::where('name', 'Superadmin')->where('guard_name', $guard)->first();
        if ($superadmin) {
            $allPerms = Permission::where('guard_name', $guard)->pluck('name')->toArray();
            $superadmin->syncPermissions($allPerms);
        }

        $this->command->info("Admin permissions seeded: {$created} created, {$skipped} skipped.");
    }
}
