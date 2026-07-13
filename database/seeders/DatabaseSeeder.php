<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EnglishLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(EnglishLevelSeeder::class);
        $this->call(ThemeSeeder::class);
        $this->call(QuestionSeeder::class);
        $this->call(QuestionCategorySeeder::class);

    User::factory()->create(['english_level_id' => EnglishLevel::first()->id]);
    }
}