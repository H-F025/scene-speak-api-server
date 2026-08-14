<?php

namespace App\Services\Speech;

use App\Models\Question;
use Illuminate\Support\Str;

/**
 * フェーズ1の判定実装。外部APIに依存せず、文字列類似度のみで評価する。
 * 想定表現の言い換えや自然さの判定はできないため、フェーズ2で LLM 実装に差し替える前提
 */
class SimilarityAnswerEvaluator implements AnswerEvaluatorInterface
{
    // この値以上の類似度であれば「自然な表現」とみなす
    private const NATURAL_THRESHOLD = 80;

    public function evaluate(Question $question, string $transcript): EvaluationResult
    {
        $normalized = $this->normalize($transcript);

        $score = 0;

        foreach ($question->expectedExpressions as $expression) {
            similar_text($normalized, $this->normalize($expression->text), $percent);
            $score = max($score, (int) round($percent));
        }

        return new EvaluationResult(
            score: $score,
            isNatural: $score >= self::NATURAL_THRESHOLD,
            feedback: $this->buildFeedback($score),
        );
    }

    /**
     * 大文字小文字・句読点・連続する空白の差異がスコアに影響しないよう正規化する
     */
    private function normalize(string $text): string
    {
        $lowered = Str::lower(trim($text));
        $withoutPunctuation = preg_replace('/[^\p{L}\p{N}\s]/u', '', $lowered);

        return preg_replace('/\s+/', ' ', $withoutPunctuation);
    }

    private function buildFeedback(int $score): string
    {
        return match (true) {
            $score >= 90 => '想定表現とほぼ一致しています。',
            $score >= 80 => '想定表現に近い言い方ができています。',
            $score >= 50 => '意味は伝わりますが、想定表現とは少し離れています。',
            default => '想定表現とは異なる回答です。模範解答を確認してみましょう。',
        };
    }
}
