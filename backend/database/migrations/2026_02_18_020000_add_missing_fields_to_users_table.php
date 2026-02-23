<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'membership_status')) {
                $table->string('membership_status')->default('Unpaid')->after('status');
            }
            if (!Schema::hasColumn('users', 'shop_name')) {
                $table->string('shop_name')->nullable()->after('membership_status');
            }
            if (!Schema::hasColumn('users', 'expire_date')) {
                $table->string('expire_date')->nullable()->after('shop_name');
            }
            if (!Schema::hasColumn('users', 'p_system')) {
                $table->string('p_system')->nullable()->after('expire_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['membership_status', 'shop_name', 'expire_date', 'p_system']);
        });
    }
};
