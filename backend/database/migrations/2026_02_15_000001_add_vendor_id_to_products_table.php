<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('id')->index();
                $table->foreign('vendor_id')
                    ->references('id')
                    ->on('vendors')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};
