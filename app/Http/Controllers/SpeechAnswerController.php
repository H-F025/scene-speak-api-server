<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpeechAnswerRequest;
use App\Models\Question;
use App\Models\SpeechAttempt;
use App\Services\Speech\AnswerEvaluatorInterface;
use Illuminate\Http\JsonResponse;

class SpeechAnswerController extends Controller
{
    // 判定処理はインターフェース経由で解決する。LLM 判定に差し替えてもこのクラスは変更不要
    public function __construct(
        private readonly AnswerEvaluatorInterface $evaluator,
    ) {
    }

    public function store(
        StoreSpeechAnswerRequest $request,
        Question $question,
    ): JsonResponse {
        $question->load('expectedExpressions');

        $result = $this->evaluator->evaluate($question, $request->validated('transcript'));

        $attempt = SpeechAttempt::create([
            'user_id' => $request->user()->id,
            'question_id' => $question->id,
            'learning_session_id' => $request->validated('learning_session_id'),
            'transcript' => $request->validated('transcript'),
            'score' => $result->score,
            'is_natural' => $result->isNatural,
            'feedback' => $result->feedback,
        ]);

        return response()->json([
            'speech_attempt_id' => $attempt->id,
            'score' => $result->score,
            'is_natural' => $result->isNatural,
            'feedback' => $result->feedback,
            'expected_expression' => $question->expectedExpressions
                ->firstWhere('is_primary', true)?->text,
        ], 201);
    }
}