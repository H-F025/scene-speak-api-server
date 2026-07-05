<?php

namespace Database\Factories;

use App\Models\ThemeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThemeLevel>
 */
class ThemeLevelFactory extends Factory
{
    public function definition(): array
    {
        static $sortOrder = 1;

        return [
            'estimated_minutes' => $this->faker->optional()->numberBetween(5, 30),
            'sort_order' => $sortOrder++,
        ];
    }
}