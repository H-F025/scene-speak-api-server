<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('review_sets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users');
        $table->string('status', 30);
        $table->dateTime('target_from_at');
        $table->dateTime('target_to_at');
        $table->unsignedSmallInteger('target_question_count');
        $table->string('priority', 20);
        $table->unsignedInteger('estimated_seconds');
        $table->unsignedSmallInteger('correct_count');
        $table->unsignedSmallInteger('incorrect_count');
        $table->unsignedSmallInteger('skipped_count');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_sets');
    }
};
