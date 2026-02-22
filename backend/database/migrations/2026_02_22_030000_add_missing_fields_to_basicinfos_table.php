<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsToBasicinfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('basicinfos', function (Blueprint $table) {
            if (!Schema::hasColumn('basicinfos', 'wp_number')) {
                $table->string('wp_number')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'fav_icon')) {
                $table->text('fav_icon')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'bonus_percent')) {
                $table->string('bonus_percent')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'wp_link')) {
                $table->string('wp_link')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'messanger_link')) {
                $table->string('messanger_link')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'near_dhaka_charge')) {
                $table->string('near_dhaka_charge')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'b_one')) {
                $table->string('b_one')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'b_two')) {
                $table->string('b_two')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'b_three')) {
                $table->string('b_three')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'title')) {
                $table->string('title')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'meta_keyword')) {
                $table->text('meta_keyword')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'meta_image')) {
                $table->text('meta_image')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'invoice_footer')) {
                $table->text('invoice_footer')->nullable();
            }
            if (!Schema::hasColumn('basicinfos', 'marquee_text')) {
                $table->text('marquee_text')->nullable();
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
            $table->dropColumn([
                'wp_number', 'fav_icon', 'bonus_percent', 'wp_link', 'messanger_link',
                'near_dhaka_charge', 'b_one', 'b_two', 'b_three', 'title',
                'meta_description', 'meta_keyword', 'meta_image', 'invoice_footer', 'marquee_text'
            ]);
        });
    }
}
