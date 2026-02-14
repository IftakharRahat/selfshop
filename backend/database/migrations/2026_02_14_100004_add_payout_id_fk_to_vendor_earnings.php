<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_earnings', function (Blueprint $table) {
            $table->foreign('payout_id')->references('id')->on('vendor_payouts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_earnings', function (Blueprint $table) {
            $table->dropForeign(['payout_id']);
        });
    }
};
