<?php

namespace Database\Factories;

use App\Models\ReviewQuestionState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewQuestionState>
 */
class ReviewQuestionStateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    return [
        'status' => 'needs_review',
        'resolved_at' => null,
        'incorrect_count' => 1,
        ];
    }
}
