<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    public function definition(): array
    {
        static $sortOrder = 1;

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'sort_order' => $sortOrder++,
        ];
    }
}