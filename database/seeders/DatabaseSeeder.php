<?php

namespace Database\Seeders;

use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
    $this->call(EnglishLevelSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'english_level_id' => EnglishLevel::where('code', 'beginner')->value('id'),
        ]);
    }
}