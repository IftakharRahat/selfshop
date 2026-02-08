<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPriceTier;
use Illuminate\Database\Seeder;

class ProductPriceTierSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::where('status', 'Active')->take(20)->get();
        foreach ($products as $product) {
            if (ProductPriceTier::where('product_id', $product->id)->exists()) {
                continue;
            }
            $base = (float) ($product->ProductResellerPrice ?? $product->min_sell_price ?? 100);
            ProductPriceTier::insert([
                ['product_id' => $product->id, 'min_qty' => 0, 'unit_price' => $base, 'tier_label' => 'Tier 1', 'created_at' => now(), 'updated_at' => now()],
                ['product_id' => $product->id, 'min_qty' => 50, 'unit_price' => round($base * 0.95, 2), 'tier_label' => 'Tier 2', 'created_at' => now(), 'updated_at' => now()],
                ['product_id' => $product->id, 'min_qty' => 100, 'unit_price' => round($base * 0.90, 2), 'tier_label' => 'Tier 3', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
