<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class FixAdminRoleSeeder extends Seeder
{
    /**
     * Ensure the default super admin has 'Superadmin' role and no 'Shop' role.
     * Uses the actual role names from the production database.
     */
    public function run(): void
    {
        $admin = Admin::where('email', 'md.muraiem@gmail.com')->first();
        if (!$admin) {
            $this->command->info('Admin md.muraiem@gmail.com not found.');
            return;
        }

        // Use the actual role name from DB (capitalized 'Superadmin', guard 'admin')
        $superadminRole = Role::where('guard_name', 'admin')
            ->whereIn('name', ['Superadmin', 'superadmin', 'admin', 'Admin'])
            ->first();

        if (!$superadminRole) {
            $this->command->error('No Superadmin/admin role found in roles table!');
            return;
        }

        $admin->syncRoles([$superadminRole->name]);
        $this->command->info("Admin #{$admin->id} ({$admin->email}) now has role: {$superadminRole->name}");
    }
}
