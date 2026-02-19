<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('productrequests')) {
            return;
        }

        $hasQuantity = Schema::hasColumn('productrequests', 'p_quantity');
        $hasDescription = Schema::hasColumn('productrequests', 'p_description');

        if ($hasQuantity && $hasDescription) {
            return;
        }

        Schema::table('productrequests', function (Blueprint $table) use ($hasQuantity, $hasDescription) {
            if (!$hasQuantity) {
                $table->string('p_quantity', 50)->nullable()->after('p_name');
            }
            if (!$hasDescription) {
                $table->text('p_description')->nullable()->after('p_quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('productrequests')) {
            return;
        }

        $hasQuantity = Schema::hasColumn('productrequests', 'p_quantity');
        $hasDescription = Schema::hasColumn('productrequests', 'p_description');

        if (!$hasQuantity && !$hasDescription) {
            return;
        }

        Schema::table('productrequests', function (Blueprint $table) use ($hasQuantity, $hasDescription) {
            if ($hasDescription) {
                $table->dropColumn('p_description');
            }
            if ($hasQuantity) {
                $table->dropColumn('p_quantity');
            }
        });
    }
};

