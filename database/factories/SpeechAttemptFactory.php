<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SpeechAttemptFactory extends Factory
{
    public function definition(): array
    {
        $score = $this->faker->numberBetween(0, 100);

        return [
            'learning_session_id' => null,
            'transcript' => $this->faker->sentence(),
            'score' => $score,
            'is_natural' => $score >= 80,
            'feedback' => $this->faker->sentence(),
        ];
    }
}
