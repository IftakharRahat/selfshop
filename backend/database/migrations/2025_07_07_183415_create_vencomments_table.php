<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVencommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vencomments', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->nullable();
            $table->string('type')->default('Withdraw');
            $table->string('comment');
            $table->integer('shop_id')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('blance')->nullable();
            $table->string('status')->default('Pending');
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
        Schema::dropIfExists('vencomments');
    }
}
