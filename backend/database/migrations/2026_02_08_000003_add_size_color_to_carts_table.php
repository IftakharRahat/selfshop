<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Support size/color for cart lines (used by add-to-cart and bulk vendor cart).
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'size')) {
                $table->string('size')->nullable()->after('qty');
            }
            if (!Schema::hasColumn('carts', 'color')) {
                $table->string('color')->nullable()->after('size');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'size')) {
                $table->dropColumn('size');
            }
            if (Schema::hasColumn('carts', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
