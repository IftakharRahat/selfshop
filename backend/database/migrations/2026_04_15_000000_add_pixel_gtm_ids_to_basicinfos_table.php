<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('basicinfos', function (Blueprint $table) {
            if (!Schema::hasColumn('basicinfos', 'facebook_pixel_id')) {
                $table->string('facebook_pixel_id')->nullable()->after('facebook_pixel');
            }
            if (!Schema::hasColumn('basicinfos', 'gtm_id')) {
                $table->string('gtm_id')->nullable()->after('google_analytics');
            }
        });
    }

    public function down()
    {
        Schema::table('basicinfos', function (Blueprint $table) {
            if (Schema::hasColumn('basicinfos', 'facebook_pixel_id')) {
                $table->dropColumn('facebook_pixel_id');
            }
            if (Schema::hasColumn('basicinfos', 'gtm_id')) {
                $table->dropColumn('gtm_id');
            }
        });
    }
};
