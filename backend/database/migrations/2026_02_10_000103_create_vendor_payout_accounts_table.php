<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorPayoutAccountsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->index();

            // bank, mobile_wallet, other
            $table->string('channel_type');
            $table->string('provider_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable();

            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payout_accounts');
    }
}

