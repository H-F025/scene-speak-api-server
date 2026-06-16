<?php

namespace Database\Seeders;
use App\Models\EnglishLevel;
use Illuminate\Database\Seeder;
class EnglishLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'code' => 'beginner',
                'name' => '初級',
                'description' => '英語にまだ自信がない',
                'example_sentence' => 'A coffee, please.',
                'sort_order' => 1,
            ],
            [
                'code' => 'intermediate',
                'name' => '中級',
                'description' => '日常会話はある程度できる',
                'example_sentence' => 'Could you recommend a good restaurant nearby?',
                'sort_order' => 2,
            ],
            [
                'code' => 'advanced',
                'name' => '上級',
                'description' => 'ビジネス英語もこなせる',
                'example_sentence' => 'I\'d like to discuss the quarterly report.',
                'sort_order' => 3,
            ],
        ];
        foreach ($levels as $level) {
            EnglishLevel::updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }
    }
}