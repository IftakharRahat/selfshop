<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'extra_delivery_per_qty')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('extra_delivery_per_qty', 10, 2)->default(0)->after('ex_dvc');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('extra_delivery_per_qty');
        });
    }
};
