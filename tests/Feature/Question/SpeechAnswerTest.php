<?php

namespace Tests\Feature\Question;

use App\Models\EnglishLevel;
use App\Models\Question;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeechAnswerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ThemeLevel $themeLevel;

    private Question $question;

    private const EXPECTED_TEXT = 'Could I get a coffee, please?';

    protected function setUp(): void
    {
        parent::setUp();

        $englishLevel = EnglishLevel::factory()->create();
        $theme = Theme::factory()->create();

        $this->themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $englishLevel->id,
        ]);

        $this->user = User::factory()->create([
            'english_level_id' => $englishLevel->id,
        ]);

        $this->question = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'sort_order' => 1,
        ]);

        $this->question->expectedExpressions()->create([
            'text' => self::EXPECTED_TEXT,
            'is_primary' => true,
        ]);
    }

    private function endpoint(?int $questionId = null): string
    {
        return '/api/v1/questions/'.($questionId ?? $this->question->id).'/speech-answers';
    }

    public function test_想定表現と一致する回答を送信すると高いスコアが返る(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint(), ['transcript' => self::EXPECTED_TEXT]);

        $response->assertCreated()
            ->assertJson([
                'score' => 100,
                'is_natural' => true,
                'expected_expression' => self::EXPECTED_TEXT,
            ]);
    }

    public function test_判定結果がspeech_attemptsに保存される(): void
    {
        $this->actingAs($this->user)
            ->postJson($this->endpoint(), ['transcript' => self::EXPECTED_TEXT])
            ->assertCreated();

        $this->assertDatabaseHas('speech_attempts', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'transcript' => self::EXPECTED_TEXT,
            'score' => 100,
            'is_natural' => true,
        ]);
    }

    public function test_想定表現と異なる回答は低いスコアになる(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint(), ['transcript' => 'Where is the station?']);

        $response->assertCreated()
            ->assertJson(['is_natural' => false]);

        $this->assertLessThan(80, $response->json('score'));
    }

    public function test_想定表現が未登録の問題は422が返る(): void
    {
        $question = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'sort_order' => 2,
        ]);

        $this->actingAs($this->user)
            ->postJson($this->endpoint($question->id), ['transcript' => self::EXPECTED_TEXT])
            ->assertUnprocessable();

        $this->assertDatabaseCount('speech_attempts', 0);
    }

    public function test_回答内容が未入力の場合はバリデーションエラーになる(): void
    {
        $this->actingAs($this->user)
            ->postJson($this->endpoint(), ['transcript' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('transcript');

        $this->assertDatabaseCount('speech_attempts', 0);
    }

    public function test_存在しない学習セッションIDを指定した場合はバリデーションエラーになる(): void
    {
        $this->actingAs($this->user)
            ->postJson($this->endpoint(), [
                'transcript' => self::EXPECTED_TEXT,
                'learning_session_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('learning_session_id');
    }

    public function test_存在しない問題IDを指定した場合は404が返る(): void
    {
        $this->actingAs($this->user)
            ->postJson($this->endpoint(999999), ['transcript' => self::EXPECTED_TEXT])
            ->assertNotFound();
    }

    public function test_未ログインの場合は401が返る(): void
    {
        $this->postJson($this->endpoint(), ['transcript' => self::EXPECTED_TEXT])
            ->assertUnauthorized();

        $this->assertDatabaseCount('speech_attempts', 0);
    }
}
