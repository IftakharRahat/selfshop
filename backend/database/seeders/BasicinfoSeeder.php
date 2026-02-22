<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Basicinfo;
use Illuminate\Support\Facades\DB;

class BasicinfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('basicinfos')->truncate();

        Basicinfo::create([
            'phone_one' => '0123456789',
            'phone_two' => '0123456780',
            'email' => 'admin@selfshop.com',
            'logo' => 'public/webview/assets/images/logo.png',
            'fav_icon' => 'public/webview/assets/images/fav.png',
            'address' => 'Dhaka, Bangladesh',
            'inside_dhaka_charge' => '60',
            'outside_dhaka_charge' => '120',
            'insie_dhaka' => 'Inside Dhaka',
            'outside_dhaka' => 'Outside Dhaka',
            'cash_on_delivery' => 'Available',
            'title' => 'Selfshop',
            'meta_description' => 'Selfshop - Your best shop',
            'meta_keyword' => 'shop, ecommerce',
            'meta_image' => 'public/webview/assets/images/logo.png',
            'facebook' => '#',
            'twitter' => '#',
            'google' => '#',
            'rss' => '#',
            'pinterest' => '#',
            'linkedin' => '#',
            'youtube' => '#',
        ]);
    }
}
