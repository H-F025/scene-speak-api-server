<?php

namespace Database\Factories;

use App\Models\ThemeLearningProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThemeLearningProgress>
 */
class ThemeLearningProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
    return [
        'status' => 'in_progress',
        'completed_problem_count' => 0,
        'study_seconds' => 0,
        'last_studied_at' => now(),
        'completed_at' => null,
        ];
    }
}
