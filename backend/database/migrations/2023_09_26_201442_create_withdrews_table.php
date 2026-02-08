<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrews', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('paymenttype_name');
            $table->longText('to_account_number');
            $table->longText('to_additional_info')->nullable();
            $table->string('status')->default('Pending');
            $table->decimal('withdraw_amount')->default(0);
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
        Schema::dropIfExists('withdrews');
    }
}
