<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('review_set_questions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('review_set_id')->constrained('review_sets');
        $table->foreignId('question_id')->constrained('questions');
        $table->foreignId('question_attempt_id')->nullable()->constrained('question_attempts');
        $table->unsignedSmallInteger('order_no');
        $table->string('result', 20);
        $table->dateTime('answered_at')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_set_questions');
    }
};
