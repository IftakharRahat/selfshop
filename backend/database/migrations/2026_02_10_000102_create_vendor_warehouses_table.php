<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->index();

            $table->string('name');
            $table->string('label')->nullable();

            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('postcode')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();

            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

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
        Schema::dropIfExists('vendor_warehouses');
    }
}

