<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('carrybee_store_id')->nullable()->after('slug');
            $table->unsignedInteger('pickup_city_id')->nullable()->after('carrybee_store_id');
            $table->unsignedInteger('pickup_zone_id')->nullable()->after('pickup_city_id');
            $table->unsignedInteger('pickup_area_id')->nullable()->after('pickup_zone_id');
            $table->string('pickup_address')->nullable()->after('pickup_area_id');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'carrybee_store_id',
                'pickup_city_id',
                'pickup_zone_id',
                'pickup_area_id',
                'pickup_address',
            ]);
        });
    }
};
