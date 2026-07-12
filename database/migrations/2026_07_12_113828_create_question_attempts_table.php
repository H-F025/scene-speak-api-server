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
    Schema::create('question_attempts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('learning_session_id')->constrained('learning_sessions');
        $table->foreignId('question_id')->constrained('questions');
        $table->foreignId('question_choice_id')->constrained('question_choices');
        $table->string('attempt_type', 50);
        $table->boolean('is_correct');
        $table->dateTime('answered_at');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_attempts');
    }
};
