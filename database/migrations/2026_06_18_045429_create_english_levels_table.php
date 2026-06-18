<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('english_levels', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 50)->unique();
            $table->string('name', 50);
            $table->string('description', 255);
            $table->string('example_sentence', 255);
            $table->unsignedInteger('sort_order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_levels');
    }
};
