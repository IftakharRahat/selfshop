<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Minicategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        Subcategory::truncate();
        Minicategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            [
                'category_name' => 'Electronics',
                'subcategories' => [
                    [
                        'sub_category_name' => 'Mobile Phones',
                        'minicategories' => ['Smartphones', 'Feature Phones']
                    ],
                    [
                        'sub_category_name' => 'Laptops',
                        'minicategories' => ['Gaming Laptops', 'Ultrabooks']
                    ]
                ]
            ],
            [
                'category_name' => 'Fashion',
                'subcategories' => [
                    [
                        'sub_category_name' => 'Men\'s Clothing',
                        'minicategories' => ['Shirts', 'T-Shirts', 'Pants']
                    ],
                    [
                        'sub_category_name' => 'Women\'s Clothing',
                        'minicategories' => ['Saree', 'Lehenga', 'Western Wear']
                    ]
                ]
            ],
            [
                'category_name' => 'Home & Kitchen',
                'subcategories' => [
                    [
                        'sub_category_name' => 'Kitchen Appliances',
                        'minicategories' => ['Blenders', 'Ovens']
                    ]
                ]
            ]
        ];

        foreach ($categories as $catData) {
            $category = Category::create([
                'category_name' => $catData['category_name'],
                'status' => 'Active',
                'front_status' => 0,
            ]);

            foreach ($catData['subcategories'] as $subcatData) {
                $subcategory = Subcategory::create([
                    'category_id' => $category->id,
                    'sub_category_name' => $subcatData['sub_category_name'],
                    'status' => 'Active',
                ]);

                foreach ($subcatData['minicategories'] as $miniName) {
                    Minicategory::create([
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory->id,
                        'mini_category_name' => $miniName,
                        'status' => 'Active',
                    ]);
                }
            }
        }
    }
}
