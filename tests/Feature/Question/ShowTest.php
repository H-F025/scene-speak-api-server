<?php

namespace Tests\Feature\Question;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\Theme;
use App\Models\ThemeLearningProgress;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ThemeLevel $themeLevel;

    private Question $question;

    private LearningSession $learningSession;

    protected function setUp(): void
    {
        parent::setUp();

        $englishLevel = EnglishLevel::factory()->create(['code' => 'beginner', 'name' => '初級', 'sort_order' => 1]);
        $theme = Theme::factory()->create(['name' => 'カフェで注文', 'sort_order' => 1]);
        $this->themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $englishLevel->id,
            'sort_order' => 1,
        ]);
        $this->user = User::factory()->create(['english_level_id' => $englishLevel->id]);

        $this->question = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'number' => 1,
            'title' => '注文する',
            'scene_label' => '店員さん',
            'partner_message' => 'What can I get for you today?',
            'instruction' => '次の日本語を英語にしましょう',
            'question' => 'コーヒーを一つください。',
            'hint' => '丁寧に注文するときは Could I get〜 が自然です。',
            'sort_order' => 1,
        ]);

        $this->learningSession = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $this->themeLevel->id,
            'learning_target_type' => 'normal',
            'status' => 'in_progress',
        ]);
    }

    public function test_returns_question_with_choices_and_progress(): void
    {
        QuestionChoice::factory()->create([
            'question_id' => $this->question->id,
            'content' => 'I want a coffee.',
            'is_correct' => false,
            'sort_order' => 1,
        ]);
        QuestionChoice::factory()->create([
            'question_id' => $this->question->id,
            'content' => 'Could I get a coffee, please?',
            'is_correct' => true,
            'sort_order' => 2,
        ]);

        ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
            'completed_problem_count' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'progress' => ['current_question_number', 'total_question_count', 'completed_question_count', 'remaining_question_count'],
                'question' => ['id', 'title', 'scene_label', 'partner_message', 'instruction', 'question_text', 'hint', 'choices' => [
                    '*' => ['id', 'content'],
                ]],
            ])
            ->assertJsonPath('progress.current_question_number', 1)
            ->assertJsonPath('progress.total_question_count', 1)
            ->assertJsonPath('progress.completed_question_count', 0)
            ->assertJsonPath('progress.remaining_question_count', 1)
            ->assertJsonPath('question.id', $this->question->id)
            ->assertJsonPath('question.title', '注文する')
            ->assertJsonPath('question.question_text', 'コーヒーを一つください。')
            ->assertJsonCount(2, 'question.choices');
    }

    public function test_updates_last_activity_at(): void
    {
        $before = now()->subMinute()->toDateTimeString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}");

        $response->assertStatus(200);

        $updated = LearningSession::find($this->learningSession->id);
        $this->assertGreaterThan($before, $updated->last_activity_at);
    }

    public function test_returns_zero_progress_when_no_theme_learning_progress(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}");

        $response->assertStatus(200)
            ->assertJsonPath('progress.completed_question_count', 0)
            ->assertJsonPath('progress.remaining_question_count', 1);
    }

    public function test_returns_choices_ordered_by_sort_order(): void
    {
        $choice3 = QuestionChoice::factory()->create(['question_id' => $this->question->id, 'sort_order' => 3]);
        $choice1 = QuestionChoice::factory()->create(['question_id' => $this->question->id, 'sort_order' => 1]);
        $choice2 = QuestionChoice::factory()->create(['question_id' => $this->question->id, 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}");

        $response->assertStatus(200)
            ->assertJsonPath('question.choices.0.id', $choice1->id)
            ->assertJsonPath('question.choices.1.id', $choice2->id)
            ->assertJsonPath('question.choices.2.id', $choice3->id);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}");

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_404_when_session_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/999/questions/{$this->question->id}");

        $response->assertStatus(404)
            ->assertJson(['message' => '学習セッションが見つかりません。']);
    }

    public function test_returns_404_when_session_belongs_to_another_user(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}");

        $response->assertStatus(404)
            ->assertJson(['message' => '学習セッションが見つかりません。']);
    }

    public function test_returns_409_when_session_is_completed(): void
    {
        $this->learningSession->update(['status' => 'completed']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}");

        $response->assertStatus(409)
            ->assertJson(['message' => 'この学習セッションはすでに終了しています。']);
    }

    public function test_returns_404_when_question_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/999");

        $response->assertStatus(404)
            ->assertJson(['message' => '問題が見つかりません。']);
    }

    public function test_returns_403_when_question_does_not_belong_to_session_theme(): void
    {
        $otherThemeLevel = ThemeLevel::factory()->create([
            'theme_id' => $this->themeLevel->theme_id,
            'english_level_id' => $this->themeLevel->english_level_id,
            'sort_order' => 2,
        ]);
        $otherQuestion = Question::factory()->create([
            'theme_level_id' => $otherThemeLevel->id,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$otherQuestion->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'この学習セッションでは指定された問題を表示できません。']);
    }
}
