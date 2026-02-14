<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_earnings', function (Blueprint $table) {
            $table->decimal('paid_amount', 14, 2)->default(0)->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_earnings', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
