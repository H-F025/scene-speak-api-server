<?php

namespace Database\Factories;

use App\Models\ReviewSetQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewSetQuestion>
 */
class ReviewSetQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    return [
        'question_attempt_id' => null,
        'order_no' => 1,
        'result' => 'not_answered',
        'answered_at' => null,
        ];
    }
}
