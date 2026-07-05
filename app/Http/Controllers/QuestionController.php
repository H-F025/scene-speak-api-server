<?php

namespace App\Http\Controllers;

use App\Models\QuestionProgress;
use App\Models\ThemeLearningProgress;
use App\Models\ThemeLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    public function index(int $themeLevelId): JsonResponse
    {
        $themeLevel = ThemeLevel::with(['theme', 'englishLevel'])->find($themeLevelId);

        if (! $themeLevel) {
            return response()->json(['message' => 'テーマが見つかりません。'], 404);
        }

        $user = Auth::user();

        // 問題一覧を sort_order 昇順で取得
        $questions = $themeLevel->questions()->orderBy('sort_order', 'asc')->get();

        // テーマ学習進捗を取得
        $themeLearningProgress = ThemeLearningProgress::where('user_id', $user->id)
            ->where('theme_level_id', $themeLevelId)
            ->first();

        $totalQuestionCount = $questions->count();

        // テーマ全体の完了済みの問題数を決める
        if ($themeLearningProgress) {
            $completedQuestionCount = $themeLearningProgress->completed_problem_count;
        } else {
            $completedQuestionCount = 0;
        }

        // テーマ全体の進捗率を計算する
        if ($totalQuestionCount > 0) {
            $progressPercentage = (int) round($completedQuestionCount / $totalQuestionCount * 100);
        } else {
            $progressPercentage = 0;
        }

        // 問題ごとの進捗を取得する
        // theme_learning_progress がない場合は、
        // まだ一度もこのテーマを学習していないので、空の配列にする。
        if ($themeLearningProgress) {
            $questionProgresses = QuestionProgress::where('user_id', $user->id)
                ->where('theme_learning_progress_id', $themeLearningProgress->id)
                ->get();
        } else {
            $questionProgresses = [];
        }

        $theme = $themeLevel->theme;
        $englishLevel = $themeLevel->englishLevel;

        $questionList = [];

        // 問題一覧をループ
        foreach ($questions as $question) {
            // 最初は未完了として扱う
            $isCompleted = false;

            // この問題に対応する進捗データがあるか探す
            foreach ($questionProgresses as $questionProgress) {
                if ($questionProgress->question_id == $question->id) {
                    if ($questionProgress->is_completed) {
                        $isCompleted = true;
                    }

                    break;
                }
            }

            $questionList[] = [
                'id' => $question->id,
                'number' => $question->number,
                'title' => $question->title,
                'is_completed' => $isCompleted,
            ];
        }

        return response()->json([
            'theme' => [
                'id' => $theme->id,
                'theme_level_id' => $themeLevel->id,
                'title' => $theme->name,
                'english_level' => $englishLevel->code,
                'english_level_label' => $englishLevel->name,
                'total_question_count' => $totalQuestionCount,
                'completed_question_count' => $completedQuestionCount,
                'progress_percentage' => $progressPercentage,
            ],
            'questions' => $questionList,
        ]);
    }
}