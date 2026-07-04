<?php

namespace App\Http\Controllers;

use App\Models\EnglishLevel;
use Illuminate\Http\JsonResponse;

class EnglishLevelController extends Controller
{
    public function index(): JsonResponse
    {
        $englishLevels = EnglishLevel::orderBy('sort_order')->get(['id', 'code', 'name', 'description', 'example_sentence']);

        return response()->json([
            'english_levels' => $englishLevels,
        ]);
    }
}