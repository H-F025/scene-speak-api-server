<?php

namespace Database\Seeders;

use App\Models\EnglishLevel;
use App\Models\Theme;
use App\Models\ThemeLevel;
use Exception;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * 1. テーマを登録する
         */
        $themes = [
            ['name' => 'カフェで注文',    'description' => '飲み物・フードを英語で注文しよう',        'sort_order' => 1],
            ['name' => '空港でチェックイン', 'description' => 'フライトのチェックイン手続きを練習',       'sort_order' => 2],
            ['name' => 'ホテルで質問',    'description' => 'フロントへの問い合わせフレーズ集',          'sort_order' => 3],
            ['name' => '自己紹介',       'description' => '名前・趣味・仕事を英語で伝えよう',          'sort_order' => 4],
            ['name' => '仕事の打ち合わせ', 'description' => 'ビジネス英語の基本会話を習得',             'sort_order' => 5],
            ['name' => 'フリートーク',    'description' => '自由な会話で表現力をアップ！',             'sort_order' => 6],
        ];

        foreach ($themes as $theme) {
            Theme::updateOrCreate(
                ['name' => $theme['name']],
                ['description' => $theme['description'], 'sort_order' => $theme['sort_order']]
            );
        }

        /*
         * 2. レベル別テーマを登録する
         */
        $themeLevels = [
            // 初級
            ['theme_name' => 'カフェで注文',    'english_level_code' => 'beginner', 'estimated_minutes' => 10,   'sort_order' => 1],
            ['theme_name' => '空港でチェックイン', 'english_level_code' => 'beginner', 'estimated_minutes' => 15,   'sort_order' => 2],
            ['theme_name' => 'ホテルで質問',    'english_level_code' => 'beginner', 'estimated_minutes' => 10,   'sort_order' => 3],
            ['theme_name' => '自己紹介',       'english_level_code' => 'beginner', 'estimated_minutes' => 8,    'sort_order' => 4],
            ['theme_name' => '仕事の打ち合わせ', 'english_level_code' => 'beginner', 'estimated_minutes' => 20,   'sort_order' => 5],
            ['theme_name' => 'フリートーク',    'english_level_code' => 'beginner', 'estimated_minutes' => null, 'sort_order' => 6],
            // 中級・上級は省略（実際のファイルには全件記載）
        ];

        foreach ($themeLevels as $themeLevel) {
            $theme = Theme::where('name', $themeLevel['theme_name'])->first();

            if ($theme === null) {
                throw new Exception('テーマが見つかりません: '.$themeLevel['theme_name']);
            }

            $englishLevel = EnglishLevel::where('code', $themeLevel['english_level_code'])->first();

            if ($englishLevel === null) {
                throw new Exception('英語レベルが見つかりません: '.$themeLevel['english_level_code']);
            }

            ThemeLevel::updateOrCreate(
                ['theme_id' => $theme->id, 'english_level_id' => $englishLevel->id],
                ['estimated_minutes' => $themeLevel['estimated_minutes'], 'sort_order' => $themeLevel['sort_order']]
            );
        }
    }
}