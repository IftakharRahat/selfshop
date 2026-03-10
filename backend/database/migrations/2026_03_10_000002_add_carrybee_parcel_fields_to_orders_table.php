<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('carrybee_parcel_id')->nullable()->after('steadfast_last_synced_at');
            $table->string('carrybee_tracking_code')->nullable()->after('carrybee_parcel_id');
            $table->string('carrybee_status')->nullable()->after('carrybee_tracking_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['carrybee_parcel_id', 'carrybee_tracking_code', 'carrybee_status']);
        });
    }
};
