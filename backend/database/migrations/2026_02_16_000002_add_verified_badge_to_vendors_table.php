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
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'is_verified_badge')) {
                $table->boolean('is_verified_badge')->default(false)->after('status')->index();
            }

            if (!Schema::hasColumn('vendors', 'verified_badge_at')) {
                $table->timestamp('verified_badge_at')->nullable()->after('is_verified_badge');
            }

            if (!Schema::hasColumn('vendors', 'verified_badge_by')) {
                $table->unsignedBigInteger('verified_badge_by')->nullable()->after('verified_badge_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'verified_badge_by')) {
                $table->dropColumn('verified_badge_by');
            }

            if (Schema::hasColumn('vendors', 'verified_badge_at')) {
                $table->dropColumn('verified_badge_at');
            }

            if (Schema::hasColumn('vendors', 'is_verified_badge')) {
                $table->dropColumn('is_verified_badge');
            }
        });
    }
};
