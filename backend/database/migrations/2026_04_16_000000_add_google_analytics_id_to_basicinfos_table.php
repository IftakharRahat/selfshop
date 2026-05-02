<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('basicinfos', function (Blueprint $table) {
            if (!Schema::hasColumn('basicinfos', 'google_analytics_id')) {
                $table->string('google_analytics_id')->nullable()->after('google_analytics');
            }
        });
    }

    public function down()
    {
        Schema::table('basicinfos', function (Blueprint $table) {
            if (Schema::hasColumn('basicinfos', 'google_analytics_id')) {
                $table->dropColumn('google_analytics_id');
            }
        });
    }
};
