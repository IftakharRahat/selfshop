<?php
/**
 * Import categories, subcategories, minicategories and brands
 * from the production API into the local database.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Minicategory;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$log = "";

// ── 1. Fetch from production API ──
$log .= "Fetching categories from production API...\n";
$catJson = file_get_contents('https://api-v1.selfshop.com.bd/api/categories');
$catResponse = json_decode($catJson, true);

$log .= "Fetching brands from production API...\n";
$brandJson = file_get_contents('https://api-v1.selfshop.com.bd/api/brands');
$brandResponse = json_decode($brandJson, true);

if (!$catResponse['status'] || !$brandResponse['status']) {
    $log .= "ERROR: API returned false status\n";
    file_put_contents(__DIR__ . '/import_production.log', $log);
    echo "Failed. Check import_production.log\n";
    exit(1);
}

// ── 2. Clear existing data ──
$log .= "Clearing existing categories, subcategories, minicategories, brands...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
Minicategory::truncate();
Subcategory::truncate();
Category::truncate();
Brand::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

// ── 3. Import Categories + Subcategories + Minicategories ──
$catCount = 0;
$subCount = 0;
$miniCount = 0;

foreach ($catResponse['data'] as $cat) {
    $category = Category::create([
        'category_name' => $cat['category_name'],
        'slug'          => $cat['slug'],
        'category_icon' => $cat['category_icon'] ?? null,
        'status'        => $cat['status'] ?? 'Active',
        'front_status'  => 0,
    ]);
    $catCount++;

    if (!empty($cat['subcategories'])) {
        foreach ($cat['subcategories'] as $sub) {
            $subcategory = Subcategory::create([
                'category_id'       => $category->id,
                'sub_category_name' => $sub['sub_category_name'],
                'slug'              => $sub['slug'],
                'subcategory_icon'  => $sub['subcategory_icon'] ?? null,
                'status'            => 'Active',
            ]);
            $subCount++;

            if (!empty($sub['minicategories'])) {
                foreach ($sub['minicategories'] as $mini) {
                    Minicategory::create([
                        'category_id'       => $category->id,
                        'subcategory_id'    => $subcategory->id,
                        'mini_category_name'=> $mini['mini_category_name'],
                        'slug'              => $mini['slug'],
                        'minicategory_icon' => $mini['minicategory_icon'] ?? null,
                        'status'            => $mini['status'] ?? 'Active',
                    ]);
                    $miniCount++;
                }
            }
        }
    }
}

// ── 4. Import Brands ──
$brandCount = 0;
foreach ($brandResponse['data'] as $br) {
    Brand::create([
        'brand_name' => $br['brand_name'],
        'slug'       => $br['slug'],
        'brand_icon' => $br['brand_icon'] ?? null,
        'status'     => $br['status'] ?? 'Active',
    ]);
    $brandCount++;
}

$log .= "\n=== IMPORT COMPLETE ===\n";
$log .= "Categories:      $catCount\n";
$log .= "Subcategories:   $subCount\n";
$log .= "Minicategories:  $miniCount\n";
$log .= "Brands:          $brandCount\n";

file_put_contents(__DIR__ . '/import_production.log', $log);
echo $log;
