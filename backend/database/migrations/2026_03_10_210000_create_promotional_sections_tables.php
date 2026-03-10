<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create promotional_sections table
        Schema::create('promotional_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('banner_image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Create pivot table
        Schema::create('promotional_section_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('promotional_sections')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['section_id', 'product_id']);
        });

        // 3. Seed the 6 existing sections
        $sections = [
            ['title' => 'Hot Selling',       'slug' => 'hot_selling',       'sort_order' => 1],
            ['title' => 'Ready To Boost',     'slug' => 'ready_to_boost',    'sort_order' => 2],
            ['title' => 'Limited Offers',     'slug' => 'limited_offers',    'sort_order' => 3],
            ['title' => 'New Arrivals',       'slug' => 'new_arrivals',      'sort_order' => 4],
            ['title' => 'Best Sellers',       'slug' => 'best_sellers',      'sort_order' => 5],
            ['title' => 'Trending Now',       'slug' => 'trending_now',      'sort_order' => 6],
        ];

        $now = now();
        foreach ($sections as $section) {
            DB::table('promotional_sections')->insert(array_merge($section, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // 4. Migrate existing product flags into the pivot table
        $flagMap = [
            'hot_list'         => 'hot_selling',
            'ready_bost'       => 'ready_to_boost',
            'limited'          => 'limited_offers',
            'show_new_product' => 'new_arrivals',
            'profitable'       => 'best_sellers',
            'summer'           => 'trending_now',
        ];

        foreach ($flagMap as $column => $slug) {
            $sectionId = DB::table('promotional_sections')->where('slug', $slug)->value('id');
            if (!$sectionId) continue;

            $productIds = DB::table('products')->where($column, 'On')->pluck('id');
            $order = 0;
            foreach ($productIds as $pid) {
                DB::table('promotional_section_products')->insertOrIgnore([
                    'section_id' => $sectionId,
                    'product_id' => $pid,
                    'sort_order' => $order++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotional_section_products');
        Schema::dropIfExists('promotional_sections');
    }
};
