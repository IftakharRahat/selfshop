<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTargetParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_target_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_target_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->decimal('achieved_value', 12, 2)->nullable();
            $table->decimal('progress_percent', 5, 2)->nullable();
            $table->string('claimed_reward_type')->nullable();
            $table->decimal('claimed_reward_value', 12, 2)->nullable();
            $table->string('claimed_reward_note')->nullable();
            $table->timestamps();

            $table->unique(['sales_target_id', 'user_id'], 'sales_target_user_unique');
            $table->index(['user_id', 'reward_claimed_at'], 'sales_target_user_claim_idx');
            $table->index(['sales_target_id', 'joined_at'], 'sales_target_joined_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_target_participants');
    }
}
