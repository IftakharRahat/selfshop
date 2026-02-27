<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariantSizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variant_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('varient_id')->constrained('varients')->onDelete('cascade');
            $table->string('size_name');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('qty')->default(0);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
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
        Schema::dropIfExists('variant_sizes');
    }
}
