<?php

namespace App\Services\Speech;

use App\Models\Question;

interface AnswerEvaluatorInterface
{
    /**
     * 文字起こし済みのユーザー回答を、問題に紐づく想定表現と突き合わせて評価する。
     *
     * @param  Question  $question  expectedExpressions をロード済みであること
     * @param  string  $transcript  文字起こし済みのユーザー回答
     */
    public function evaluate(Question $question, string $transcript): EvaluationResult;
}
