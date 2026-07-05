<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    static $sortOrder = 1;

    return [
        'number' => $sortOrder,
        'title' => $this->faker->word(),
        'scene_label' => $this->faker->word(),
        'partner_message' => $this->faker->sentence(),
        'instruction' => $this->faker->sentence(),
        'question' => $this->faker->sentence(),
        'hint' => $this->faker->sentence(),
        'correct_explanation' => $this->faker->sentence(),
        'incorrect_explanation' => $this->faker->sentence(),
        'sort_order' => $sortOrder++,
        ];
    }
}
