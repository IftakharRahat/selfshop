<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->index();
            $table->unsignedBigInteger('payout_request_id')->nullable()->index();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status', 20)->default('completed')->index(); // pending, completed, failed
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('payout_request_id')->references('id')->on('vendor_payout_requests')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payouts');
    }
};
