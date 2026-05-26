<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure products.warranty_days column exists
        if (!Schema::hasColumn('products', 'warranty_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('warranty_days')->nullable()->after('status');
            });
        }

        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number', 20)->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedInteger('warranty_days');
            $table->date('delivered_at');
            $table->date('warranty_expires_at');
            $table->text('reason');
            $table->json('images')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->unsignedBigInteger('responded_by')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');

        if (Schema::hasColumn('products', 'warranty_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('warranty_days');
            });
        }
    }
};
