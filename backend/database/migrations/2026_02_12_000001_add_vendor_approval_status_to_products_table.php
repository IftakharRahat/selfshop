<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'vendor_approval_status')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('vendor_approval_status', 20)->nullable()->after('vendor_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'vendor_approval_status')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('vendor_approval_status');
            });
        }
    }
};
