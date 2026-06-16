<?php

namespace Database\Factories;

use App\Models\EnglishLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnglishLevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'beginner',
            'name' => '初級',
            'description' => '英語にまだ自信がない',
            'example_sentence' => 'A coffee, please.',
            'sort_order' => 1,
        ];
    }
}
