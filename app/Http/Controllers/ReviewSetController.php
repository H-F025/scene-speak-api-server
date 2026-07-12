<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\ReviewQuestionState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewSetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

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
}