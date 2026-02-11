<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorKycDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->index();

            // e.g. nid, trade_license, tin, other
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->string('document_path')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_kyc_documents');
    }
}

