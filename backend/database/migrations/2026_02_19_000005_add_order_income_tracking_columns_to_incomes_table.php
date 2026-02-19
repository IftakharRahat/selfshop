<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOrderIncomeTrackingColumnsToIncomesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasColumn('incomes', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->index()->after('from');
            }

            if (!Schema::hasColumn('incomes', 'invoice_code')) {
                $table->string('invoice_code')->nullable()->after('order_id');
            }
        });

        // Best-effort backfill for legacy rows where invoice_id stored order primary key.
        if (Schema::hasColumn('incomes', 'order_id') && Schema::hasColumn('incomes', 'invoice_id')) {
            DB::table('incomes')
                ->whereNull('order_id')
                ->where('invoice_id', '>', 0)
                ->update(['order_id' => DB::raw('invoice_id')]);
        }

        // Best-effort backfill invoice_code from orders table.
        if (Schema::hasColumn('incomes', 'invoice_code') && Schema::hasColumn('incomes', 'order_id')) {
            try {
                DB::statement("
                    UPDATE incomes i
                    INNER JOIN orders o ON o.id = i.order_id
                    SET i.invoice_code = o.invoiceID
                    WHERE i.invoice_code IS NULL
                ");
            } catch (\Throwable $e) {
                // Ignore if SQL dialect does not support this statement.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (Schema::hasColumn('incomes', 'invoice_code')) {
                $table->dropColumn('invoice_code');
            }

            if (Schema::hasColumn('incomes', 'order_id')) {
                $table->dropColumn('order_id');
            }
        });
    }
}
