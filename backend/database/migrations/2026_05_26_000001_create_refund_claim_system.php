<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'warranty_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('warranty_days')->nullable()->index();
            });
        }

        if (Schema::hasTable('orderproducts') && !Schema::hasColumn('orderproducts', 'warranty_days_snapshot')) {
            Schema::table('orderproducts', function (Blueprint $table) {
                $table->unsignedInteger('warranty_days_snapshot')->nullable()->index();
            });
        }

        if (!Schema::hasTable('refund_claims')) {
            Schema::create('refund_claims', function (Blueprint $table) {
                $table->id();
                $table->string('claim_number')->unique();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('orderproduct_id')->unique();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('status', 32)->default('pending')->index();
                $table->date('delivery_date');
                $table->dateTime('expires_at')->index();
                $table->unsignedInteger('warranty_days');
                $table->text('message');
                $table->string('image_path')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('refund_claim_messages')) {
            Schema::create('refund_claim_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('refund_claim_id')->index();
                $table->string('sender_type', 16)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('admin_id')->nullable()->index();
                $table->text('message');
                $table->string('attachment_path')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_claim_messages');
        Schema::dropIfExists('refund_claims');

        if (Schema::hasTable('orderproducts') && Schema::hasColumn('orderproducts', 'warranty_days_snapshot')) {
            Schema::table('orderproducts', function (Blueprint $table) {
                $table->dropColumn('warranty_days_snapshot');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'warranty_days')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('warranty_days');
            });
        }
    }
};
