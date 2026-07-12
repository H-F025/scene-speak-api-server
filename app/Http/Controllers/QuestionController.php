<?php

namespace App\Http\Controllers;

use App\Models\QuestionProgress;
use App\Models\ThemeLearningProgress;
use App\Models\ThemeLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\LearningSession;
use App\Models\Question;

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

    public function show(int $learningSessionId, int $questionId): JsonResponse
    {
    // ログイン中のユーザーを取得する
    $user = Auth::user();

    // URLで受け取った学習セッションIDが、
    // ログイン中のユーザーの学習セッションか確認する
    $learningSession = LearningSession::where('id', $learningSessionId)
        ->where('user_id', $user->id)
        ->first();

    // 学習セッションが見つからない場合
    if (! $learningSession) {
        return response()->json([
            'message' => '学習セッションが見つかりません。',
        ], 404);
    }

    // すでに終了している学習セッションでは問題を表示できない
    if ($learningSession->status !== 'in_progress') {
        return response()->json([
            'message' => 'この学習セッションはすでに終了しています。',
        ], 409);
    }

    // URLで受け取った問題IDに該当する問題を取得する
    $question = Question::find($questionId);

    // 問題が見つからない場合
    if (! $question) {
        return response()->json([
            'message' => '問題が見つかりません。',
        ], 404);
    }

    // この学習セッションで表示できる問題か確認する
    // learning_target_id には theme_level_id が入っている想定
    if ($question->theme_level_id !== $learningSession->learning_target_id) {
        return response()->json([
            'message' => 'この学習セッションでは指定された問題を表示できません。',
        ], 403);
    }

    // ログイン中のユーザーが、このテーマレベルをどこまで進めているか取得する
    $themeLearningProgress = ThemeLearningProgress::where('user_id', $user->id)
        ->where('theme_level_id', $question->theme_level_id)
        ->first();

    // 完了済みの問題数を入れる変数
    $completedQuestionCount = 0;

    // 進捗データがある場合だけ、完了済み問題数を取得する
    if ($themeLearningProgress) {
        $completedQuestionCount = $themeLearningProgress->completed_problem_count;
    }

    // このテーマレベルに紐づく全問題数を取得する
    $totalQuestionCount = Question::where('theme_level_id', $question->theme_level_id)
        ->count();

    // 残りの問題数を計算する
    $remainingQuestionCount = $totalQuestionCount - $completedQuestionCount;

    // 念のため、残り問題数がマイナスにならないようにする
    if ($remainingQuestionCount < 0) {
        $remainingQuestionCount = 0;
    }

    // 学習中の最後の活動時刻を更新する
    // 問題画面を表示したタイミングも学習中の活動として扱う
    $learningSession->last_activity_at = now();
    $learningSession->save();

    // 問題に紐づく選択肢を表示順で取得する
    $questionChoices = $question->choices()
        ->orderBy('sort_order', 'asc')
        ->get();

    // レスポンス用の選択肢配列を作る
    $choices = [];

    // 画面に返したい項目だけを配列に入れる
    foreach ($questionChoices as $choice) {
        $choices[] = [
            'id' => $choice->id,
            'content' => $choice->content,
        ];
    }

    // 問題情報と進捗情報をJSONで返す
    return response()->json([
        'progress' => [
            'current_question_number' => $question->number,
            'total_question_count' => $totalQuestionCount,
            'completed_question_count' => $completedQuestionCount,
            'remaining_question_count' => $remainingQuestionCount,
        ],
        'question' => [
            'id' => $question->id,
            'title' => $question->title,
            'scene_label' => $question->scene_label,
            'partner_message' => $question->partner_message,
            'instruction' => $question->instruction,
            'question_text' => $question->question,
            'hint' => $question->hint,
            'choices' => $choices,
        ],
    ]);
    }
}