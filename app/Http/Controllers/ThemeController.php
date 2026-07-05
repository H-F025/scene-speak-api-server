<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexThemeRequest;
use App\Models\EnglishLevel;
use App\Models\ThemeLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    public function index(IndexThemeRequest $request): JsonResponse
    {
        $user = Auth::user();

        // 1. 英語レベルを決める
        if ($request->filled('english_level')) {
            // クエリパラメータがある場合
            $englishLevelCode = $request->input('english_level');
            $englishLevel = EnglishLevel::where('code', $englishLevelCode)->first();
        } else {
            // クエリパラメータがない場合は、ログインユーザーの英語レベルを使う
            $englishLevel = $user->englishLevel;
        }

        // 2. 指定された英語レベルのテーマレベルを取得する
        $themeLevels = ThemeLevel::with(['theme', 'englishLevel'])
            ->where('english_level_id', $englishLevel->id)
            ->orderBy('sort_order', 'asc')
            ->get();

        // 3. レスポンス用の配列を作る
        $themes = [];

        foreach ($themeLevels as $themeLevel) {
            $theme = $themeLevel->theme;
            $englishLevel = $themeLevel->englishLevel;

            $themes[] = [
                'id' => $theme->id,
                'theme_level_id' => $themeLevel->id,
                'title' => $theme->name,
                'description' => $theme->description,
                'english_level' => $englishLevel->code,
                'english_level_label' => $englishLevel->name,
                'estimated_minutes' => $themeLevel->estimated_minutes,
            ];
        }

        // 4. JSONで返す
        return response()->json([
            'themes' => $themes,
        ]);
    }
}