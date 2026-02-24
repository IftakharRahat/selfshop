<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Brand::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $brands = [
            'Samsung',
            'Apple',
            'Sony',
            'Nike',
            'Adidas',
            'Puma',
            'LG',
            'Panasonic'
        ];

        foreach ($brands as $brandName) {
            Brand::create([
                'brand_name' => $brandName,
                'slug' => Str::slug($brandName),
                'status' => 'Active',
            ]);
        }
    }
}
