<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlugToSubcategoriesAndMinicategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('subcategories', 'slug')) {
                $table->string('slug')->after('sub_category_name')->nullable();
            }
        });

        Schema::table('minicategories', function (Blueprint $table) {
            if (!Schema::hasColumn('minicategories', 'slug')) {
                $table->string('slug')->after('mini_category_name')->nullable();
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
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('minicategories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
