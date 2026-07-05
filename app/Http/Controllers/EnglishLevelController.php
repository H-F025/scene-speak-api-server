<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEnglishLevelRequest;
use App\Models\EnglishLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EnglishLevelController extends Controller
{
    public function index(): JsonResponse
    {
        $englishLevels = EnglishLevel::orderBy('sort_order')->get(['id', 'code', 'name', 'description', 'example_sentence']);

        return response()->json([
            'english_levels' => $englishLevels,
        ]);
    }

    public function update(UpdateEnglishLevelRequest $request): JsonResponse
    {
        $englishLevel = EnglishLevel::find($request->id);

        if (! $englishLevel) {
            return response()->json(['message' => '英語レベルが見つかりません。'], 404);
        }

        $user = Auth::user();
        $user->update(['english_level_id' => $englishLevel->id]);

        return response()->json(['message' => '英語レベルを更新しました。']);
    }
}