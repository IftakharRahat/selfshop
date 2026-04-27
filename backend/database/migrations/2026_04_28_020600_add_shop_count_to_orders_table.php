<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks how many distinct shops/suppliers are in a single order.
     * Used for delivery charge calculation: deliveryCharge = baseCharge × shop_count
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('shop_count')->default(1)->after('store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shop_count');
        });
    }
};
