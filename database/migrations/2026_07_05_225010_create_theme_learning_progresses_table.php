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
    Schema::create('theme_learning_progresses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->foreignId('theme_level_id')->constrained('theme_levels');
        $table->string('status', 50);
        $table->unsignedSmallInteger('completed_problem_count');
        $table->unsignedInteger('study_seconds');
        $table->dateTime('last_studied_at');
        $table->dateTime('completed_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_learning_progresses');
    }
};
