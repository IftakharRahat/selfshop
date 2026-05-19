<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_payout_accounts')) {
            Schema::create('user_payout_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('paymenttype_id')->index();
                $table->string('channel_type')->default('mobile_wallet');
                $table->string('provider_name')->nullable();
                $table->string('account_name')->nullable();
                $table->string('account_number');
                $table->string('bank_name')->nullable();
                $table->string('branch_name')->nullable();
                $table->string('routing_number')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->unique(['user_id', 'paymenttype_id']);
            });
        }

        if (Schema::hasTable('withdrews') && !Schema::hasColumn('withdrews', 'user_payout_account_id')) {
            $hasPaymenttypeId = Schema::hasColumn('withdrews', 'paymenttype_id');

            Schema::table('withdrews', function (Blueprint $table) use ($hasPaymenttypeId) {
                $column = $table->unsignedBigInteger('user_payout_account_id')->nullable();
                if ($hasPaymenttypeId) {
                    $column->after('paymenttype_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('withdrews') && Schema::hasColumn('withdrews', 'user_payout_account_id')) {
            Schema::table('withdrews', function (Blueprint $table) {
                $table->dropColumn('user_payout_account_id');
            });
        }

        Schema::dropIfExists('user_payout_accounts');
    }
};
