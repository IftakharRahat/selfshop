<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Run in this order: roles first, then admin, then fix default admin role, then vendor examples
        // NOTE: Commented out items can be enabled when you are ready.

        // $this->call(RolePermissionSeeder::class);
        // $this->call(AdminSeeder::class);
        // $this->call(FixAdminRoleSeeder::class);
        // $this->call(InformationSeeder::class);
        // $this->call(UserSeeder::class);
        // $this->call(UserRolePermissionSeeder::class);
        // $this->call(ProductPriceTierSeeder::class); // Vendor wholesale tiers (Tier 1/2/3) for first 20 products

        // Minimal vendor demo data for local/testing environments only.
        if (app()->environment(['local', 'development'])) {
            $this->call(VendorDemoSeeder::class);
        }

    }
}