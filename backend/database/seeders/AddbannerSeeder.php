<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Addbanner;

class AddbannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Addbanner::truncate();

        $banners = [
            [
                'id' => 1,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/1.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/2.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 3,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/3.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 4,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/4.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 5,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/5.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 6,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/6.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 7,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/7.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 8,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/8.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 9,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/9.jpg',
                'status' => 'Active',
            ],
            [
                'id' => 10,
                'add_link' => '#',
                'add_image' => 'public/webview/assets/images/banner/10.jpg',
                'status' => 'Active',
            ],
        ];

        foreach ($banners as $banner) {
            Addbanner::create($banner);
        }
    }
}
