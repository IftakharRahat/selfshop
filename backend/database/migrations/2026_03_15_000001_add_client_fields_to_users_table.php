<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds columns from the client's existing users table (hudai.users)
     * so that their user data can be imported alongside our existing users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'order_bonus')) {
                $table->float('order_bonus')->default(0)->after('account_balance');
            }
            if (!Schema::hasColumn('users', 'sell_profit')) {
                $table->float('sell_profit')->default(0)->after('order_bonus');
            }
            if (!Schema::hasColumn('users', 'bonus_percent')) {
                $table->float('bonus_percent')->default(0)->after('cashout_balance');
            }
            if (!Schema::hasColumn('users', 'is_invoice')) {
                $table->string('is_invoice')->nullable()->after('remember_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['order_bonus', 'sell_profit', 'bonus_percent', 'is_invoice']);
        });
    }
};
