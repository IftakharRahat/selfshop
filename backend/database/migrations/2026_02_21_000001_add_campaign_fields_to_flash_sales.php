<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCampaignFieldsToFlashSales extends Migration
{
    public function up()
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->string('banner_image')->nullable()->after('status');
            $table->dateTime('registration_deadline')->nullable()->after('end_time');
            $table->boolean('vendor_registration')->default(true)->after('registration_deadline');
        });
    }

    public function down()
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropColumn(['banner_image', 'registration_deadline', 'vendor_registration']);
        });
    }
}
