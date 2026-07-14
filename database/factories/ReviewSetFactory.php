<?php

namespace Database\Factories;

use App\Models\ReviewSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewSet>
 */
class ReviewSetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    return [
        'status' => 'created',
        'target_from_at' => now()->subDays(6)->startOfDay(),
        'target_to_at' => now(),
        'target_question_count' => 1,
        'priority' => 'low',
        'estimated_seconds' => 45,
        'correct_count' => 0,
        'incorrect_count' => 0,
        'skipped_count' => 0,
        ];
    }
}
