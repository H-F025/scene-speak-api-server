<?php

namespace Tests\Feature\Question;

use App\Models\EnglishLevel;
use App\Models\Question;
use App\Models\QuestionProgress;
use App\Models\Theme;
use App\Models\ThemeLearningProgress;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ThemeLevel $themeLevel;

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
    }

    public function test_returns_questions_with_theme_info(): void
    {
        $question1 = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'number' => 1,
            'title' => '注文する',
            'sort_order' => 1,
        ]);
        $question2 = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'number' => 2,
            'title' => '挨拶する',
            'sort_order' => 2,
        ]);
        $question3 = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'number' => 3,
            'title' => 'メニューを見る',
            'sort_order' => 3,
        ]);

        $themeLearningProgress = ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
            'completed_problem_count' => 2,
        ]);

        QuestionProgress::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question1->id,
            'theme_learning_progress_id' => $themeLearningProgress->id,
            'is_completed' => true,
        ]);
        QuestionProgress::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question2->id,
            'theme_learning_progress_id' => $themeLearningProgress->id,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/themes/{$this->themeLevel->id}/questions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'theme' => ['id', 'theme_level_id', 'title', 'english_level', 'english_level_label', 'total_question_count', 'completed_question_count', 'progress_percentage'],
                'questions' => [
                    '*' => ['id', 'number', 'title', 'is_completed'],
                ],
            ])
            ->assertJsonPath('theme.total_question_count', 3)
            ->assertJsonPath('theme.completed_question_count', 2)
            ->assertJsonPath('theme.progress_percentage', 67)
            ->assertJsonPath('questions.0.is_completed', true)
            ->assertJsonPath('questions.1.is_completed', true)
            ->assertJsonPath('questions.2.is_completed', false);
    }

    public function test_returns_zero_progress_when_no_theme_learning_progress(): void
    {
        // テーマ学習進捗がない状態（まだ一度も学習していないユーザー）
        Question::factory()->create(['theme_level_id' => $this->themeLevel->id, 'sort_order' => 1]);
        Question::factory()->create(['theme_level_id' => $this->themeLevel->id, 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/themes/{$this->themeLevel->id}/questions");

        $response->assertStatus(200)
            ->assertJsonPath('theme.completed_question_count', 0)
            ->assertJsonPath('theme.progress_percentage', 0)
            ->assertJsonPath('questions.0.is_completed', false)
            ->assertJsonPath('questions.1.is_completed', false);
    }

    public function test_returns_questions_ordered_by_sort_order(): void
    {
        // sort_order がバラバラな順番で登録されていても、昇順で返ることを確認する
        $question3 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id, 'sort_order' => 3]);
        $question1 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id, 'sort_order' => 1]);
        $question2 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id, 'sort_order' => 2]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/themes/{$this->themeLevel->id}/questions");

        $response->assertStatus(200)
            ->assertJsonPath('questions.0.id', $question1->id)
            ->assertJsonPath('questions.1.id', $question2->id)
            ->assertJsonPath('questions.2.id', $question3->id);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson("/api/v1/themes/{$this->themeLevel->id}/questions");

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_404_when_theme_level_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/themes/999/questions');

        $response->assertStatus(404)
            ->assertJson(['message' => 'テーマが見つかりません。']);
    }
}