<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorFollowersTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'vendor_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_followers');
    }
}
