<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppVersionFieldsToBasicinfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('basicinfos', function (Blueprint $table) {
            if (!Schema::hasColumn('basicinfos', 'android_app_version_code')) {
                $table->unsignedInteger('android_app_version_code')->nullable()->default(1);
            }
            if (!Schema::hasColumn('basicinfos', 'android_play_store_url')) {
                $table->string('android_play_store_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basicinfos', function (Blueprint $table) {
            $table->dropColumn(['android_app_version_code', 'android_play_store_url']);
        });
    }
}
