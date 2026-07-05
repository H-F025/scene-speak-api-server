<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('theme_levels', function (Blueprint $table) {
        $table->id();
        $table->foreignId('theme_id')->constrained('themes');
        $table->unsignedTinyInteger('english_level_id');
        $table->foreign('english_level_id')->references('id')->on('english_levels');
        $table->unsignedInteger('estimated_minutes')->nullable();
        $table->unsignedInteger('sort_order');
        $table->timestamps();
    });
}

    public function down(): void
    {
    Schema::dropIfExists('theme_levels');
    }
};
