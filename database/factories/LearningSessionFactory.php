<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LearningSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'learning_target_type' => 'normal',
            'status' => 'in_progress',
            'started_at' => now(),
            'last_activity_at' => now(),
            'ended_at' => null,
            'duration_seconds' => 0,
        ];
    }
}
