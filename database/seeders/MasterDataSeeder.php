<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * 本番環境でも安全に繰り返し実行できるマスタデータ系シーダーのみをまとめる。
     * updateOrCreate ベースで冪等なため、デプロイのたびに実行してよい。
     * User::factory() でテストユーザーを作成する DatabaseSeeder 本体は含めない。
     */
    public function run(): void
    {
        $this->call(EnglishLevelSeeder::class);
        $this->call(ThemeSeeder::class);
        $this->call(QuestionSeeder::class);
        $this->call(QuestionCategorySeeder::class);
        $this->call(GuestUserSeeder::class);
    }
}
