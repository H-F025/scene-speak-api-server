<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('expected_expressions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('question_id')->constrained()->cascadeOnDelete();
        $table->string('text');
        $table->boolean('is_primary')->default(false);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expected_expressions');
    }
};
