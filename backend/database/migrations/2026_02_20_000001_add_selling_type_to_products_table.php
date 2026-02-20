<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'selling_type')) {
                $table->string('selling_type', 20)->default('both')->after('allow_dropship')
                    ->comment('wholesale, dropshipping, or both');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'selling_type')) {
                $table->dropColumn('selling_type');
            }
        });
    }
};
