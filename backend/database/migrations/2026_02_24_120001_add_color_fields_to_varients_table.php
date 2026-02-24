<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('varients')) {
            return;
        }

        Schema::table('varients', function (Blueprint $table) {
            if (!Schema::hasColumn('varients', 'color_name')) {
                $table->string('color_name', 100)->nullable()->after('title');
            }
            if (!Schema::hasColumn('varients', 'color_code')) {
                $table->string('color_code', 10)->nullable()->after('color_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('varients')) {
            return;
        }

        Schema::table('varients', function (Blueprint $table) {
            if (Schema::hasColumn('varients', 'color_code')) {
                $table->dropColumn('color_code');
            }
            if (Schema::hasColumn('varients', 'color_name')) {
                $table->dropColumn('color_name');
            }
        });
    }
};

