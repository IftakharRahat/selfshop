<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('target_type', ['quantity', 'amount'])->default('amount');
            $table->decimal('target_value', 12, 2)->default(0);
            $table->enum('order_scope', ['non_canceled', 'delivered'])->default('non_canceled');
            $table->enum('reward_type', ['bonus', 'gift', 'reward'])->default('reward');
            $table->decimal('reward_value', 12, 2)->nullable();
            $table->string('reward_note')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('priority')->default(0);
            $table->string('status')->default('Active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_date', 'end_date'], 'sales_targets_status_window_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_targets');
    }
}
