<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->unique();
            $table->string('otp')->nullable();
            $table->string('my_referral_code');
            $table->string('refer_by');
            $table->string('password');

            $table->text('profile')->nullable();
            $table->text('nid')->nullable();
            $table->text('dob')->nullable();
            $table->text('address')->nullable();

            $table->integer('my_referral')->default(0);
            $table->float('referal_bonus')->default(0);
            $table->float('total_account_balance')->default(0);
            $table->float('account_balance')->default(0);
            $table->float('pending_cashout_balance')->default(0);
            $table->float('cashout_balance')->default(0);

            $table->string('status')->default('Inactive');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
