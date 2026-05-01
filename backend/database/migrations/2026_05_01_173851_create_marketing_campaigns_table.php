<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Display name: "Sahib's Campaign"
            $table->string('code')->unique();          // URL slug: "sahib"
            $table->unsignedBigInteger('created_by');  // Admin who created it
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
