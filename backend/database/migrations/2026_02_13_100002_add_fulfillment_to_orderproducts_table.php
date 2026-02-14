<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orderproducts', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('quantity');
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->string('fulfillment_status')->default('pending')->after('shipped_at'); // pending, shipped, delivered
            $table->string('fulfillment_type')->nullable()->after('fulfillment_status'); // standard, dropship
        });
    }

    public function down(): void
    {
        Schema::table('orderproducts', function (Blueprint $table) {
            $table->dropColumn(['tracking_number', 'shipped_at', 'fulfillment_status', 'fulfillment_type']);
        });
    }
};
