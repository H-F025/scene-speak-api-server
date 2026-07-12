<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionChoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(),
            'is_correct' => false,
            'sort_order' => 1,
        ];
    }
}
