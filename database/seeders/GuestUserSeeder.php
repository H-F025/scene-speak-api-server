<?php

namespace Database\Seeders;

use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuestUserSeeder extends Seeder
{
    /**
     * TOP ページのゲストログイン導線で使用する固定デモアカウント。
     * migrate:fresh --seed のたびに同一の認証情報で復元されるよう updateOrCreate を使う
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'ゲストユーザー',
                'password' => Hash::make('QymBqPG6'),
                'email_verified_at' => now(),
                'english_level_id' => EnglishLevel::first()->id,
            ],
        );
    }
}
