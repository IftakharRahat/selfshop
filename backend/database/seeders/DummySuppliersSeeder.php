<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummySuppliersSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'GreenLeaf Trade', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'BlueSky Wholesale', 'city' => 'Chittagong', 'type' => 'Wholesaler'],
            ['name' => 'SilkWay Fashion', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'Golden Stitch BD', 'city' => 'Gazipur', 'type' => 'Manufacturer'],
            ['name' => 'Pearl Harbor Tex', 'city' => 'Narayanganj', 'type' => 'Wholesaler'],
            ['name' => 'Crimson Thread', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'NovaTex Industries', 'city' => 'Chittagong', 'type' => 'Manufacturer'],
            ['name' => 'EverGreen Apparel', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'SunRise Collection', 'city' => 'Sylhet', 'type' => 'Wholesaler'],
            ['name' => 'DiamondWear BD', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'OceanPearl Garments', 'city' => 'Chittagong', 'type' => 'Manufacturer'],
            ['name' => 'TigerLily Fashion', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'RoyalStitch Hub', 'city' => 'Gazipur', 'type' => 'Manufacturer'],
            ['name' => 'AquaMarine Traders', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'PlatinumWear Co', 'city' => 'Narayanganj', 'type' => 'Manufacturer'],
            ['name' => 'MoonBeam Textiles', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'StarLight Fabrics', 'city' => 'Chittagong', 'type' => 'Manufacturer'],
            ['name' => 'UrbanWeave BD', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'CoralReef Fashion', 'city' => 'Sylhet', 'type' => 'Wholesaler'],
            ['name' => 'SapphireGlow Tex', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'AmberWorks BD', 'city' => 'Gazipur', 'type' => 'Manufacturer'],
            ['name' => 'CrystalWear Hub', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'JadeStone Traders', 'city' => 'Chittagong', 'type' => 'Wholesaler'],
            ['name' => 'OpalDream Garments', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'RubyVibe Fashion', 'city' => 'Narayanganj', 'type' => 'Manufacturer'],
            ['name' => 'TopazEdge BD', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'EmeraldPeak Tex', 'city' => 'Gazipur', 'type' => 'Manufacturer'],
            ['name' => 'VelvetNest Hub', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'SilverLine Apparel', 'city' => 'Chittagong', 'type' => 'Manufacturer'],
            ['name' => 'BronzeAge Traders', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'IronClad Garments', 'city' => 'Sylhet', 'type' => 'Manufacturer'],
            ['name' => 'CopperField BD', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'ZincWave Fashion', 'city' => 'Gazipur', 'type' => 'Wholesaler'],
            ['name' => 'NickelVault Tex', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'TinForge Hub', 'city' => 'Narayanganj', 'type' => 'Wholesaler'],
            ['name' => 'LeadStar Traders', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'ChromeEdge BD', 'city' => 'Chittagong', 'type' => 'Wholesaler'],
            ['name' => 'TitanWear Fashion', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'CobaltBlue Tex', 'city' => 'Gazipur', 'type' => 'Manufacturer'],
            ['name' => 'ManganeseHub BD', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'TungstenPro Tex', 'city' => 'Sylhet', 'type' => 'Manufacturer'],
            ['name' => 'MolybdenumWorks', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
            ['name' => 'PalladiumGlow BD', 'city' => 'Chittagong', 'type' => 'Manufacturer'],
            ['name' => 'IridiumVibe Tex', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'OsmiumWear Hub', 'city' => 'Narayanganj', 'type' => 'Wholesaler'],
            ['name' => 'RhodiumPeak BD', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'RutheniumEdge Tex', 'city' => 'Gazipur', 'type' => 'Wholesaler'],
            ['name' => 'IndiumStar Fashion', 'city' => 'Dhaka', 'type' => 'Manufacturer'],
            ['name' => 'HafniumLine BD', 'city' => 'Chittagong', 'type' => 'Manufacturer'],
            ['name' => 'TantalumCraft Tex', 'city' => 'Dhaka', 'type' => 'Wholesaler'],
        ];

        $now = now();

        foreach ($suppliers as $i => $s) {
            $slug = Str::slug($s['name']);

            // Check if vendor with this slug already exists
            if (DB::table('vendors')->where('slug', $slug)->exists()) {
                $this->command->info("Skipping duplicate: {$s['name']}");
                continue;
            }

            // Create a user record for this vendor
            $userId = DB::table('users')->insertGetId([
                'name'       => $s['name'],
                'email'      => 'vendor_' . $slug . '@selfshop.test',
                'password'   => Hash::make('password123'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Create the vendor record
            DB::table('vendors')->insert([
                'user_id'           => $userId,
                'company_name'      => $s['name'],
                'slug'              => $slug,
                'business_type'     => $s['type'],
                'city'              => $s['city'],
                'country'           => 'Bangladesh',
                'status'            => 'approved',
                'approval_type'     => 'public',
                'is_verified_badge' => rand(0, 1),
                'contact_name'      => $s['name'] . ' Admin',
                'contact_email'     => 'contact_' . $slug . '@selfshop.test',
                'contact_phone'     => '017' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'approved_at'       => $now,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $this->command->info("Created vendor #{$i}: {$s['name']}");
        }

        $this->command->info('Done! 50 dummy suppliers seeded.');
    }
}
