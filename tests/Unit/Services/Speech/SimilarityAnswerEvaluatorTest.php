<?php

namespace Tests\Unit\Services\Speech;

use App\Models\ExpectedExpression;
use App\Models\Question;
use App\Services\Speech\SimilarityAnswerEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SimilarityAnswerEvaluatorTest extends TestCase
{
    private SimilarityAnswerEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new SimilarityAnswerEvaluator();
    }

    /**
     * DBに触れず、リレーションだけを差し込んだ Question を組み立てる
     */
    private function makeQuestion(array $texts): Question
    {
        $question = new Question();

        $question->setRelation(
            'expectedExpressions',
            collect(array_map(
                fn (string $text) => new ExpectedExpression(['text' => $text]),
                $texts,
            )),
        );

        return $question;
    }

    public function test_想定表現と完全に一致する場合はスコア100になる(): void
    {
        $question = $this->makeQuestion(['Could I get a coffee, please?']);

        $result = $this->evaluator->evaluate($question, 'Could I get a coffee, please?');

        $this->assertSame(100, $result->score);
        $this->assertTrue($result->isNatural);
    }

    #[DataProvider('normalizationProvider')]
    public function test_表記の揺れはスコアに影響しない(string $transcript): void
    {
        $question = $this->makeQuestion(['Could I get a coffee, please?']);

        $result = $this->evaluator->evaluate($question, $transcript);

        $this->assertSame(100, $result->score);
    }

    public static function normalizationProvider(): array
    {
        return [
            '大文字小文字の違い' => ['COULD I GET A COFFEE, PLEASE?'],
            '句読点なし'         => ['Could I get a coffee please'],
            '連続する空白'       => ['Could  I   get a coffee, please?'],
            '前後の空白'         => ['  Could I get a coffee, please?  '],
        ];
    }

    public function test_想定表現が複数ある場合は最も高いスコアを採用する(): void
    {
        $question = $this->makeQuestion([
            'I would like a coffee.',
            'Can I get a coffee, please?',
        ]);

        $result = $this->evaluator->evaluate($question, 'Can I get a coffee, please?');

        $this->assertSame(100, $result->score);
    }

    public function test_無関係な回答は低いスコアになる(): void
    {
        $question = $this->makeQuestion(['Could I get a coffee, please?']);

        $result = $this->evaluator->evaluate($question, 'Where is the station?');

        $this->assertLessThan(80, $result->score);
        $this->assertFalse($result->isNatural);
    }

    public function test_想定表現が空の場合はスコア0になる(): void
    {
        $question = $this->makeQuestion([]);

        $result = $this->evaluator->evaluate($question, 'Could I get a coffee, please?');

        $this->assertSame(0, $result->score);
        $this->assertFalse($result->isNatural);
    }
}