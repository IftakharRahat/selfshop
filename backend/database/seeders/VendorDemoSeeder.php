<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VendorDemoSeeder extends Seeder
{
    /**
     * Seed a couple of example vendor accounts for local testing.
     *
     * - Creates users (if they do not exist)
     * - Creates linked Vendor records with status "approved"
     */
    public function run(): void
    {
        // Vendor #1
        $user1 = User::firstOrCreate(
            ['email' => 'demo.vendor1@selfshop.test'],
            [
                'name' => 'Demo Vendor 1',
                'password' => Hash::make('password'),
                'phone' => '01000000001',
                'status' => 'Active',
                'country' => 'BD',
            ]
        );

        Vendor::firstOrCreate(
            ['user_id' => $user1->id],
            [
                'company_name' => 'Demo Vendor One Ltd.',
                'slug' => 'demo-vendor-one',
                'business_type' => 'wholesale',
                'contact_name' => 'Demo Vendor 1',
                'contact_email' => $user1->email,
                'contact_phone' => $user1->phone,
                'country' => 'BD',
                'city' => 'Dhaka',
                'status' => 'approved',
            ]
        );

        // Vendor #2
        $user2 = User::firstOrCreate(
            ['email' => 'demo.vendor2@selfshop.test'],
            [
                'name' => 'Demo Vendor 2',
                'password' => Hash::make('password'),
                'phone' => '01000000002',
                'status' => 'Active',
                'country' => 'BD',
            ]
        );

        Vendor::firstOrCreate(
            ['user_id' => $user2->id],
            [
                'company_name' => 'Demo Vendor Two Traders',
                'slug' => 'demo-vendor-two',
                'business_type' => 'dropship',
                'contact_name' => 'Demo Vendor 2',
                'contact_email' => $user2->email,
                'contact_phone' => $user2->phone,
                'country' => 'BD',
                'city' => 'Chattogram',
                'status' => 'pending',
            ]
        );
    }
}

