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
        // Run in this order: roles first, then admin, then fix default admin role
        // $this->call(RolePermissionSeeder::class);
        // $this->call(AdminSeeder::class);
        // $this->call(FixAdminRoleSeeder::class);
        // $this->call(InformationSeeder::class);
        // $this->call(UserSeeder::class);
        // $this->call(UserRolePermissionSeeder::class);
        // $this->call(ProductPriceTierSeeder::class); // Vendor wholesale tiers (Tier 1/2/3) for first 20 products
        // \App\Models\User::factory(10)->create();
        // \App\Models\Basicinfo::factory(1)->create();
        // \App\Models\Addbanner::factory(4)->create();

    }
}