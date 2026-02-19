<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products') || Schema::hasColumn('products', 'vendor_approval_status')) {
            return;
        }

        $afterColumn = Schema::hasColumn('products', 'vendor_id')
            ? 'vendor_id'
            : (Schema::hasColumn('products', 'id') ? 'id' : null);

        Schema::table('products', function (Blueprint $table) use ($afterColumn) {
            $column = $table->string('vendor_approval_status', 20)->nullable();
            if ($afterColumn) {
                $column->after($afterColumn);
            }
            $column->index();
        });
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
