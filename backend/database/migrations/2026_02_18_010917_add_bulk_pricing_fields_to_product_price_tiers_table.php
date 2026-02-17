<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBulkPricingFieldsToProductPriceTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('product_price_tiers', function (Blueprint $table) {
            $table->unsignedInteger('max_qty')->nullable()->after('min_qty');
            $table->decimal('delivery_charge', 10, 2)->nullable()->after('unit_price');
            $table->string('variant_title', 255)->nullable()->after('tier_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_price_tiers', function (Blueprint $table) {
            $table->dropColumn(['max_qty', 'delivery_charge', 'variant_title']);
        });
    }
}
