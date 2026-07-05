<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('questions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('theme_level_id')->constrained('theme_levels');
        $table->unsignedTinyInteger('number');
        $table->string('title', 100);
        $table->string('scene_label', 100);
        $table->text('partner_message');
        $table->string('instruction', 255);
        $table->text('question');
        $table->text('hint');
        $table->text('correct_explanation');
        $table->text('incorrect_explanation');
        $table->unsignedInteger('sort_order');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
