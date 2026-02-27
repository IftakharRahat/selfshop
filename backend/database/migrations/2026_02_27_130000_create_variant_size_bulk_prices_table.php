<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariantSizeBulkPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variant_size_bulk_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_size_id')->constrained('variant_sizes')->onDelete('cascade');
            $table->integer('min_qty');
            $table->integer('max_qty')->nullable();
            $table->decimal('bulk_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variant_size_bulk_prices');
    }
}
