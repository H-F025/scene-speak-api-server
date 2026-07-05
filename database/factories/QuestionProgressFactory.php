<?php

namespace Database\Factories;

use App\Models\QuestionProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionProgress>
 */
class QuestionProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    return [
        'is_completed' => true,
        'is_correct' => null,
        'completed_at' => now(),
        ];
    }
}
