<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;

class FlashSaleSeeder extends Seeder
{
    public function run()
    {
        // Skip if flash sale data already exists
        if (FlashSale::count() > 0) {
            $this->command?->info('Flash sale data already exists, skipping.');
            return;
        }

        // Create a flash sale running for 7 days from now
        $flashSale = FlashSale::create([
            'title' => 'Weekend Mega Sale',
            'start_time' => now(),
            'end_time' => now()->addDays(7),
            'status' => 'Active',
        ]);

        // Pick 6 random products and assign discounts
        $products = Product::inRandomOrder()->take(6)->get();
        $discounts = [10, 15, 20, 25, 30, 35];

        foreach ($products as $index => $product) {
            FlashSaleProduct::create([
                'flash_sale_id' => $flashSale->id,
                'product_id' => $product->id,
                'discount_percentage' => $discounts[$index] ?? 15,
            ]);
        }

        $this->command->info('Flash Sale "Weekend Mega Sale" created with ' . $products->count() . ' products!');
    }
}
