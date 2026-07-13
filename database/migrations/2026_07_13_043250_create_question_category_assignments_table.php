<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('question_category_assignments', function (Blueprint $table) {
        $table->foreignId('question_id')->constrained('questions');
        $table->foreignId('question_category_id')->constrained('question_categories');
        $table->primary(['question_id', 'question_category_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_category_assignments');
    }
};
