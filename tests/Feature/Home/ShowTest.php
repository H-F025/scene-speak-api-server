<?php

namespace Tests\Feature\Home;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\ReviewQuestionState;
use App\Models\Theme;
use App\Models\ThemeLearningProgress;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ShowTest extends TestCase
{
    use RefreshDatabase;
    private EnglishLevel $englishLevel;
    private User $user;
    protected function setUp(): void
    {
        parent::setUp();
        $this->englishLevel = EnglishLevel::factory()->create();
        $this->user = User::factory()->create([
            'english_level_id' => $this->englishLevel->id,
        ]);
    }
    public function test_returns_home_data(): void
    {
        $theme = Theme::factory()->create(['sort_order' => 1]);
        $themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $this->englishLevel->id,
            'estimated_minutes' => 10,
            'sort_order' => 1,
        ]);
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'started_at' => now(),
            'ended_at' => now()->addSeconds(600),
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'user_name' => $this->user->name,
                'stats' => [
                    'streak_days' => 1,
                    'today_study_time' => '10',
                ],
                'recommended_theme' => [
                    'theme_level_id' => $themeLevel->id,
                    'theme_id' => $theme->id,
                    'name' => $theme->name,
                    'description' => $theme->description,
                    'english_level' => $this->englishLevel->code,
                    'english_level_label' => $this->englishLevel->name,
                    'estimated_minutes' => 10,
                    'estimated_time_label' => '約10分',
                ],
                'has_review_set' => false,
            ]);
    }
    public function test_returns_null_recommended_theme_when_no_theme_levels(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'recommended_theme' => null,
            ]);
    }
    public function test_recommended_theme_estimated_time_label_is_unlimited_when_estimated_minutes_is_null(): void
    {
        $theme = Theme::factory()->create(['sort_order' => 1]);
        ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $this->englishLevel->id,
            'estimated_minutes' => null,
            'sort_order' => 1,
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'recommended_theme' => [
                    'estimated_minutes' => null,
                    'estimated_time_label' => '制限なし',
                ],
            ]);
    }
    public function test_recommends_in_progress_theme_over_not_started(): void
    {
        $theme1 = Theme::factory()->create(['sort_order' => 1]);
        $theme2 = Theme::factory()->create(['sort_order' => 2]);
        $themeLevel1 = ThemeLevel::factory()->create([
            'theme_id' => $theme1->id,
            'english_level_id' => $this->englishLevel->id,
            'sort_order' => 1,
        ]);
        $themeLevel2 = ThemeLevel::factory()->create([
            'theme_id' => $theme2->id,
            'english_level_id' => $this->englishLevel->id,
            'sort_order' => 1,
        ]);
        // themeLevel2 は未開始、themeLevel1 は学習中
        ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $themeLevel1->id,
            'status' => 'in_progress',
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'recommended_theme' => [
                    'theme_level_id' => $themeLevel1->id,
                ],
            ]);
    }
    public function test_recommends_not_started_theme_over_completed(): void
    {
        $theme1 = Theme::factory()->create(['sort_order' => 1]);
        $theme2 = Theme::factory()->create(['sort_order' => 2]);
        $themeLevel1 = ThemeLevel::factory()->create([
            'theme_id' => $theme1->id,
            'english_level_id' => $this->englishLevel->id,
            'sort_order' => 1,
        ]);
        $themeLevel2 = ThemeLevel::factory()->create([
            'theme_id' => $theme2->id,
            'english_level_id' => $this->englishLevel->id,
            'sort_order' => 1,
        ]);
        // themeLevel1 は完了済み、themeLevel2 は未開始
        ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $themeLevel1->id,
            'status' => 'completed',
            'last_studied_at' => now()->subDays(1),
            'completed_at' => now()->subDays(1),
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'recommended_theme' => [
                    'theme_level_id' => $themeLevel2->id,
                ],
            ]);
    }
    public function test_recommends_oldest_studied_theme_when_all_completed(): void
    {
        $theme1 = Theme::factory()->create(['sort_order' => 1]);
        $theme2 = Theme::factory()->create(['sort_order' => 2]);
        $themeLevel1 = ThemeLevel::factory()->create([
            'theme_id' => $theme1->id,
            'english_level_id' => $this->englishLevel->id,
            'sort_order' => 1,
        ]);
        $themeLevel2 = ThemeLevel::factory()->create([
            'theme_id' => $theme2->id,
            'english_level_id' => $this->englishLevel->id,
            'sort_order' => 1,
        ]);
        // themeLevel1 は最近完了、themeLevel2 は古く完了（より古いので優先される）
        ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $themeLevel1->id,
            'status' => 'completed',
            'last_studied_at' => now()->subDays(1),
            'completed_at' => now()->subDays(1),
        ]);
        ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $themeLevel2->id,
            'status' => 'completed',
            'last_studied_at' => now()->subDays(5),
            'completed_at' => now()->subDays(5),
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'recommended_theme' => [
                    'theme_level_id' => $themeLevel2->id,
                ],
            ]);
    }
    public function test_today_study_time_splits_cross_day_session(): void
    {
        $theme = Theme::factory()->create();
        $themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $this->englishLevel->id,
        ]);
        // 昨日23:55〜今日00:05のセッション（10分 = 600秒）
        // 当日分は00:00〜00:05の5分（300秒）のみカウントされる
        $sessionStart = now()->startOfDay()->subMinutes(5); // 昨日 23:55
        $sessionEnd = now()->startOfDay()->addMinutes(5);   // 今日 00:05
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'started_at' => $sessionStart,
            'ended_at' => $sessionEnd,
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'stats' => [
                    'today_study_time' => '5',
                ],
            ]);
    }
    public function test_today_study_time_excludes_in_progress_and_zero_duration(): void
    {
        $theme = Theme::factory()->create();
        $themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $this->englishLevel->id,
        ]);
        // in_progress は除外
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $themeLevel->id,
            'status' => 'in_progress',
            'duration_seconds' => 600,
            'started_at' => now(),
            'ended_at' => null,
        ]);
        // duration_seconds = 0 は除外
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 0,
            'started_at' => now(),
            'ended_at' => now()->addSeconds(10),
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'stats' => [
                    'today_study_time' => '0',
                ],
            ]);
    }
    public function test_returns_true_for_has_review_set(): void
    {
        $theme = Theme::factory()->create();
        $themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $this->englishLevel->id,
        ]);
        $question = Question::factory()->create(['theme_level_id' => $themeLevel->id]);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'status' => 'needs_review',
            'updated_at' => now(),
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'has_review_set' => true,
            ]);
    }
    public function test_returns_false_for_has_review_set_when_no_review_questions(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'has_review_set' => false,
            ]);
    }
    public function test_returns_false_for_has_review_set_when_review_is_older_than_7_days(): void
    {
        $theme = Theme::factory()->create();
        $themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $this->englishLevel->id,
        ]);
        $question = Question::factory()->create(['theme_level_id' => $themeLevel->id]);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'status' => 'needs_review',
            'updated_at' => now()->subDays(8),
        ]);
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/home');
        $response->assertStatus(200)
            ->assertJson([
                'has_review_set' => false,
            ]);
    }
    public function test_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/v1/home');
        $response->assertStatus(401);
    }
}