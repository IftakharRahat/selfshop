<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Brand;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $categories = Category::pluck('id')->toArray();
        $brands = Brand::pluck('id')->toArray();

        if (empty($categories) || empty($brands)) {
            $this->command->error('Categories or Brands are empty! Please seed them first.');
            return;
        }

        $products = [
            ['name' => 'Premium Cotton T-Shirt', 'regular' => 850, 'sale' => 650, 'wholesale' => 550, 'sku' => 'TS-001'],
            ['name' => 'Classic Denim Jeans', 'regular' => 2500, 'sale' => 1899, 'wholesale' => 1600, 'sku' => 'DJ-002'],
            ['name' => 'Leather Crossbody Bag', 'regular' => 3200, 'sale' => 2499, 'wholesale' => 2100, 'sku' => 'LB-003'],
            ['name' => 'Running Sneakers Pro', 'regular' => 4500, 'sale' => 3599, 'wholesale' => 3000, 'sku' => 'RS-004'],
            ['name' => 'Wireless Bluetooth Earbuds', 'regular' => 1800, 'sale' => 1299, 'wholesale' => 1100, 'sku' => 'BE-005'],
            ['name' => 'Stainless Steel Water Bottle', 'regular' => 650, 'sale' => 450, 'wholesale' => 350, 'sku' => 'WB-006'],
            ['name' => 'Organic Face Cream', 'regular' => 1200, 'sale' => 899, 'wholesale' => 750, 'sku' => 'FC-007'],
            ['name' => 'Smart Fitness Watch', 'regular' => 5500, 'sale' => 4299, 'wholesale' => 3800, 'sku' => 'FW-008'],
            ['name' => 'Bamboo Sunglasses', 'regular' => 1500, 'sale' => 999, 'wholesale' => 800, 'sku' => 'BS-009'],
            ['name' => 'Portable Power Bank 20000mAh', 'regular' => 2200, 'sale' => 1699, 'wholesale' => 1400, 'sku' => 'PB-010'],
            ['name' => 'Cotton Polo Shirt', 'regular' => 950, 'sale' => 750, 'wholesale' => 600, 'sku' => 'PS-011'],
            ['name' => 'Laptop Backpack Waterproof', 'regular' => 2800, 'sale' => 2199, 'wholesale' => 1800, 'sku' => 'LBP-012'],
            ['name' => 'LED Desk Lamp Touch', 'regular' => 1600, 'sale' => 1199, 'wholesale' => 950, 'sku' => 'DL-013'],
            ['name' => 'Ceramic Coffee Mug Set', 'regular' => 800, 'sale' => 599, 'wholesale' => 480, 'sku' => 'CM-014'],
            ['name' => 'Yoga Mat Premium 6mm', 'regular' => 1400, 'sale' => 999, 'wholesale' => 800, 'sku' => 'YM-015'],
            ['name' => 'Wireless Mouse Ergonomic', 'regular' => 1100, 'sale' => 799, 'wholesale' => 650, 'sku' => 'WM-016'],
            ['name' => 'Men Formal Leather Belt', 'regular' => 750, 'sale' => 550, 'wholesale' => 420, 'sku' => 'FB-017'],
            ['name' => 'Kitchen Knife Set 5pcs', 'regular' => 3500, 'sale' => 2699, 'wholesale' => 2200, 'sku' => 'KS-018'],
            ['name' => 'Bluetooth Speaker Portable', 'regular' => 2000, 'sale' => 1499, 'wholesale' => 1200, 'sku' => 'SP-019'],
            ['name' => 'Digital Alarm Clock', 'regular' => 900, 'sale' => 649, 'wholesale' => 500, 'sku' => 'AC-020'],
        ];

        // Download product images
        $imageDir = public_path('images/product/seed/');
        if (!file_exists($imageDir)) {
            mkdir($imageDir, 0755, true);
        }

        foreach ($products as $index => $p) {
            $catId = $categories[array_rand($categories)];
            $subcategories = Subcategory::where('category_id', $catId)->pluck('id')->toArray();
            $subcatId = !empty($subcategories) ? $subcategories[array_rand($subcategories)] : Subcategory::first()->id;
            $brandId = $brands[array_rand($brands)];

            $slug = Str::slug($p['name']);

            // Use picsum for placeholder images
            $imageFilename = 'product_' . ($index + 1) . '.jpg';
            $imagePath = 'public/images/product/seed/' . $imageFilename;
            $fullPath = $imageDir . $imageFilename;

            if (!file_exists($fullPath)) {
                $imageUrl = 'https://picsum.photos/seed/product' . ($index + 1) . '/500/500';
                $imageContent = @file_get_contents($imageUrl);
                if ($imageContent) {
                    file_put_contents($fullPath, $imageContent);
                }
            }

            Product::create([
                'category_id' => $catId,
                'subcategory_id' => $subcatId,
                'brand_id' => $brandId,
                'ProductName' => $p['name'],
                'ProductSlug' => $slug,
                'ProductImage' => $imagePath,
                'ViewProductImage' => $imagePath,
                'ProductSku' => $p['sku'],
                'ProductRegularPrice' => $p['regular'],
                'ProductSalePrice' => $p['sale'],
                'ProductWholesalePrice' => $p['wholesale'],
                'ProductResellerPrice' => $p['sale'],
                'qty' => rand(50, 200),
                'status' => 'Active',
                'ProductBreaf' => 'High quality product available at the best price.',
                'ProductDetails' => '<p>Premium quality product with excellent craftsmanship. Made from the finest materials for lasting durability and satisfaction.</p>',
                'MetaTitle' => $p['name'],
                'MetaKey' => strtolower(str_replace(' ', ',', $p['name'])),
                'MetaDescription' => 'Buy ' . $p['name'] . ' at the best price.',
                'Discount' => round((($p['regular'] - $p['sale']) / $p['regular']) * 100),
                'min_sell_price' => $p['wholesale'],
                'event' => 1,
                'frature' => rand(0, 1),
                'top_rated' => rand(0, 1),
            ]);

            $this->command->info('Created: ' . $p['name']);
        }

        $this->command->info('Successfully seeded ' . count($products) . ' products!');
    }
}
