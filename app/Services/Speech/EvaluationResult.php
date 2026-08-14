<?php

namespace App\Services\Speech;

/**
 * 音声回答の判定結果。実装クラスが変わっても呼び出し側が受け取る形を保証するため、
 * 連想配列ではなく型付きの値オブジェクトとして定義する
 */
readonly class EvaluationResult
{
    public function __construct(
        public int $score,
        public bool $isNatural,
        public ?string $feedback = null,
    ) {
    }
}
