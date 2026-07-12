<?php

namespace Database\Factories;

use App\Models\QuestionAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionAttempt>
 */
class QuestionAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    return [
        'attempt_type' => 'theme',
        'is_correct' => false,
        'answered_at' => now(),
        ];
    }
}
