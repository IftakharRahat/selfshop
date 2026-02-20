<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVendorFieldsToFlashSaleProducts extends Migration
{
    public function up()
    {
        Schema::table('flash_sale_products', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->after('product_id');
            $table->decimal('campaign_price', 10, 2)->nullable()->after('discount_percentage');
            $table->string('seller_sku')->nullable()->after('campaign_price');

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('flash_sale_products', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['vendor_id', 'campaign_price', 'seller_sku']);
        });
    }
}
