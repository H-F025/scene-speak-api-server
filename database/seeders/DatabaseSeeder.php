<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EnglishLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(EnglishLevelSeeder::class);
        $this->call(ThemeSeeder::class);
        $this->call(QuestionSeeder::class);

        User::updateOrCreate(
            [
                'email' => 'test@example.com',
            ],
            [
                'name' => 'Test User',
                'english_level_id' => EnglishLevel::where('code', 'beginner')->value('id'),
                'password' => Hash::make('password'),
            ]
        );
    }
}