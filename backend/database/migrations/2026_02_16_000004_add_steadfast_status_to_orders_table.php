<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'warehouse_sent_at')) {
                $table->timestamp('warehouse_sent_at')->nullable()->after('shipped_at');
            }
            if (!Schema::hasColumn('orders', 'steadfast_status')) {
                $table->string('steadfast_status')->nullable()->after('warehouse_sent_at');
            }
            if (!Schema::hasColumn('orders', 'steadfast_consignment_id')) {
                $table->string('steadfast_consignment_id')->nullable()->after('steadfast_status');
            }
            if (!Schema::hasColumn('orders', 'steadfast_last_synced_at')) {
                $table->timestamp('steadfast_last_synced_at')->nullable()->after('steadfast_consignment_id');
            }
            if (!Schema::hasColumn('orders', 'steadfast_payload')) {
                $table->longText('steadfast_payload')->nullable()->after('steadfast_last_synced_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $drop = [];

            if (Schema::hasColumn('orders', 'warehouse_sent_at')) {
                $drop[] = 'warehouse_sent_at';
            }
            if (Schema::hasColumn('orders', 'steadfast_status')) {
                $drop[] = 'steadfast_status';
            }
            if (Schema::hasColumn('orders', 'steadfast_consignment_id')) {
                $drop[] = 'steadfast_consignment_id';
            }
            if (Schema::hasColumn('orders', 'steadfast_last_synced_at')) {
                $drop[] = 'steadfast_last_synced_at';
            }
            if (Schema::hasColumn('orders', 'steadfast_payload')) {
                $drop[] = 'steadfast_payload';
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
