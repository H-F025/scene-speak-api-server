<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExpectedExpressionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'text' => $this->faker->sentence(),
            'is_primary' => false,
        ];
    }

    // 模範解答として画面表示する1件を作る際に使う
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}