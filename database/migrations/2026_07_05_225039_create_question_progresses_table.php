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
    Schema::create('question_progresses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('question_id')->constrained('questions');
        $table->foreignId('theme_learning_progress_id')->constrained('theme_learning_progresses');
        $table->boolean('is_completed');
        $table->boolean('is_correct')->nullable();
        $table->dateTime('completed_at');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_progresses');
    }
};
