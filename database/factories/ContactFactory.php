<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
    return [
        'user_id' => null,
        'name'    => $this->faker->name(),
        'email'   => $this->faker->safeEmail(),
        'message' => $this->faker->realText(200),
        ];
    }
}