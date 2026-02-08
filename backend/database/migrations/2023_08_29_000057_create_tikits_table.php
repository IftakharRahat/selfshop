<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTikitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tikits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('from_id');
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->string('department');
            $table->string('priority');
            $table->string('message')->nullable();
            $table->text('attachment')->nullable();
            $table->string('status')->default('Pending');
            $table->string('solved_By');
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
        Schema::dropIfExists('tikits');
    }
}
