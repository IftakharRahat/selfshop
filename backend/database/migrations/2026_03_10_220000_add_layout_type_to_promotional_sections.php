<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotional_sections', function (Blueprint $table) {
            $table->string('layout_type')->default('card')->after('banner_image'); // card or slider
            $table->string('bg_color')->nullable()->after('layout_type');          // e.g. #FDF0F6
        });
    }

    public function down(): void
    {
        Schema::table('promotional_sections', function (Blueprint $table) {
            $table->dropColumn(['layout_type', 'bg_color']);
        });
    }
};
