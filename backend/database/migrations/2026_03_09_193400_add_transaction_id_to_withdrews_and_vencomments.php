<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add transaction_id to user withdrawals
        if (Schema::hasTable('withdrews') && !Schema::hasColumn('withdrews', 'transaction_id')) {
            Schema::table('withdrews', function (Blueprint $table) {
                $table->string('transaction_id')->nullable()->after('status');
            });
        }

        // Add transaction_id to vendor/supplier withdrawals
        if (Schema::hasTable('vencomments')) {
            if (!Schema::hasColumn('vencomments', 'transaction_id')) {
                Schema::table('vencomments', function (Blueprint $table) {
                    $table->string('transaction_id')->nullable()->after('status');
                });
            }
            if (!Schema::hasColumn('vencomments', 'payment_type')) {
                Schema::table('vencomments', function (Blueprint $table) {
                    $table->string('payment_type')->nullable()->after('comment');
                });
            }
            if (!Schema::hasColumn('vencomments', 'account_number')) {
                Schema::table('vencomments', function (Blueprint $table) {
                    $table->string('account_number')->nullable()->after('payment_type');
                });
            }
            if (!Schema::hasColumn('vencomments', 'additional_info')) {
                Schema::table('vencomments', function (Blueprint $table) {
                    $table->text('additional_info')->nullable()->after('account_number');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdrews') && Schema::hasColumn('withdrews', 'transaction_id')) {
            Schema::table('withdrews', function (Blueprint $table) {
                $table->dropColumn('transaction_id');
            });
        }

        if (Schema::hasTable('vencomments')) {
            $columns = ['transaction_id', 'payment_type', 'account_number', 'additional_info'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('vencomments', $col)) {
                    Schema::table('vencomments', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
        }
    }
};
