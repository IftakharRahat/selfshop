<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Wholesale tier pricing: e.g. 1–49 @ base, 50–99 @ Tier 2, 100+ @ Tier 3.
     */
    public function up(): void
    {
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->unsignedInteger('min_qty')->default(0)->comment('Minimum quantity for this tier');
            $table->decimal('unit_price', 10, 2);
            $table->string('tier_label', 50)->default('Tier 1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_tiers');
    }
};
