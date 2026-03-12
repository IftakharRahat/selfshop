<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'profit')) {
                $table->decimal('profit', 10, 2)->default(0)->after('subTotal');
            }
            if (!Schema::hasColumn('orders', 'order_bonus')) {
                $table->decimal('order_bonus', 10, 2)->default(0)->after('profit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'profit')) {
                $table->dropColumn('profit');
            }
            if (Schema::hasColumn('orders', 'order_bonus')) {
                $table->dropColumn('order_bonus');
            }
        });
    }
};
