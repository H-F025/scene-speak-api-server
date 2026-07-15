<?php

namespace App\Http\Controllers;

use App\Models\LearningSession;
use App\Models\Question;
use App\Models\ReviewQuestionState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ReviewSet;
use App\Models\ReviewSetQuestion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

class ReviewSetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $from = now()->subDays(6)->startOfDay();

        $reviewQuestionStates = ReviewQuestionState::where('user_id', $user->id)
            ->where('status', 'needs_review')
            ->where('updated_at', '>=', $from)
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();

        $questionIds = [];

        foreach ($reviewQuestionStates as $reviewQuestionState) {
            $questionIds[] = $reviewQuestionState->question_id;
        }

        $questionCount = count($questionIds);

        if ($questionCount === 0) {
            $priority = 'non';
            $priorityLabel = null;
        } elseif ($questionCount <= 2) {
            $priority = 'low';
            $priorityLabel = '低';
        } elseif ($questionCount <= 5) {
            $priority = 'medium';
            $priorityLabel = '中';
        } else {
            $priority = 'high';
            $priorityLabel = '高';
        }

        $estimatedSeconds = $questionCount * 45;

        $estimatedMinutes = null;

        if ($questionCount > 0) {
            $estimatedMinutes = ceil($estimatedSeconds / 60);
        }

        $categories = [];

        if ($questionCount > 0) {
            $questions = Question::whereIn('id', $questionIds)
                ->with('categories')
                ->get();

            $categoryCounts = [];

            foreach ($questions as $question) {
                foreach ($question->categories as $category) {
                    if (! isset($categoryCounts[$category->id])) {
                        $categoryCounts[$category->id] = [
                            'id' => $category->id,
                            'name' => $category->name,
                            'description' => $category->description,
                            'question_count' => 0,
                            'sort_order' => $category->sort_order,
                        ];
                    }
                    $categoryCounts[$category->id]['question_count']++;
                }
            }

            usort($categoryCounts, function ($a, $b) {
                if ($a['question_count'] > $b['question_count']) {
                    return -1;
                }

                if ($a['question_count'] < $b['question_count']) {
                    return 1;
                }

                if ($a['sort_order'] < $b['sort_order']) {
                    return -1;
                }

                if ($a['sort_order'] > $b['sort_order']) {
                    return 1;
                }

                return 0;
            });

            foreach ($categoryCounts as $categoryCount) {
                $categories[] = [
                    'id' => $categoryCount['id'],
                    'name' => $categoryCount['name'],
                    'description' => $categoryCount['description'],
                    'question_count' => $categoryCount['question_count'],
                ];
            }
        }

        return response()->json([
            'question_count' => $questionCount,
            'priority' => $priority,
            'priority_label' => $priorityLabel,
            'estimated_seconds' => $estimatedSeconds,
            'estimated_minutes' => $estimatedMinutes,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
    $user = Auth::user();

    // 今日を含む直近7日間の開始日時
    $from = CarbonImmutable::now()->subDays(6)->startOfDay();
    $to = CarbonImmutable::now();

    // 復習が必要な問題を直近で間違えた順に最大8件取得する
    $reviewQuestionStates = ReviewQuestionState::where('user_id', $user->id)
        ->where('status', 'needs_review')
        ->where('updated_at', '>=', $from)
        ->orderBy('updated_at', 'desc')
        ->limit(8)
        ->get();

    $questionCount = $reviewQuestionStates->count();

    if ($questionCount === 0) {
        return response()->json(['message' => '現在、復習できる問題がありません。'], 409);
    }

    // 問題数に応じて優先度を決める
    if ($questionCount <= 2) {
        $priority = 'low';
    } elseif ($questionCount <= 5) {
        $priority = 'medium';
    } else {
        $priority = 'high';
    }

    $estimatedSeconds = $questionCount * 45;

    // 復習セットを作成する
    $reviewSet = ReviewSet::create([
        'user_id' => $user->id,
        'status' => 'in_progress',
        'target_from_at' => $from,
        'target_to_at' => $to,
        'target_question_count' => $questionCount,
        'priority' => $priority,
        'estimated_seconds' => $estimatedSeconds,
        'correct_count' => 0,
        'incorrect_count' => 0,
        'skipped_count' => 0,
    ]);

    // 復習問題を出題順付きで作成する
    $firstReviewSetQuestionId = null;
    foreach ($reviewQuestionStates as $index => $reviewQuestionState) {
        $reviewSetQuestion = ReviewSetQuestion::create([
            'review_set_id' => $reviewSet->id,
            'question_id' => $reviewQuestionState->question_id,
            'question_attempt_id' => null,
            'order_no' => $index + 1,
            'result' => 'not_answered',
            'answered_at' => null,
        ]);

        if ($index === 0) {
            $firstReviewSetQuestionId = $reviewSetQuestion->id;
        }
    }

    return response()->json([
        'review_set_id' => $reviewSet->id,
        'first_review_set_question_id' => $firstReviewSetQuestionId,
        ], 201);
    }

    public function showQuestion(int $reviewSetId, int $reviewSetQuestionId): JsonResponse
    {
    $user = Auth::user();

    // ログインユーザーの復習セットか確認する
    $reviewSet = ReviewSet::where('id', $reviewSetId)
        ->where('user_id', $user->id)
        ->first();

    if (! $reviewSet) {
        return response()->json(['message' => '復習問題が見つかりません。'], 404);
    }

    // 指定された復習問題がこの復習セットに含まれるか確認する
    $reviewSetQuestion = ReviewSetQuestion::where('id', $reviewSetQuestionId)
        ->where('review_set_id', $reviewSet->id)
        ->first();

    if (! $reviewSetQuestion) {
        return response()->json(['message' => '復習問題が見つかりません。'], 404);
    }

    // 問題のカテゴリを取得する（sort_order が小さい順で最初の1件）
    $question = $reviewSetQuestion->question;
    $category = $question->categories()->orderBy('sort_order', 'asc')->first();
    $categoryName = $category ? $category->name : null;

    // 復習セット内の進捗を計算する
    $totalQuestionCount = $reviewSet->target_question_count;
    $completedQuestionCount = $reviewSet->reviewSetQuestions()
        ->where('result', '!=', 'not_answered')
        ->count();
    $remainingQuestionCount = $totalQuestionCount - $completedQuestionCount;

    // 対応する学習セッションが in_progress の場合、last_activity_at を更新する
    $learningSession = LearningSession::where('user_id', $user->id)
        ->where('learning_target_type', 'review')
        ->where('learning_target_id', $reviewSet->id)
        ->where('status', 'in_progress')
        ->first();

    if ($learningSession) {
        $learningSession->update(['last_activity_at' => now()]);
    }

    // 問題に紐づく選択肢を表示順で取得する
    $choices = [];
    foreach ($question->choices()->orderBy('sort_order', 'asc')->get() as $choice) {
        $choices[] = [
            'id' => $choice->id,
            'content' => $choice->content,
        ];
    }

    return response()->json([
        'review_set_id' => $reviewSet->id,
        'category_name' => $categoryName,
        'progress' => [
            'current_question_number' => $reviewSetQuestion->order_no,
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