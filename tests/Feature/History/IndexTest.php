<?php

namespace Tests\Feature\History;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\QuestionChoice;
use App\Models\ReviewSet;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class IndexTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private EnglishLevel $englishLevel;
    private Theme $theme;
    private ThemeLevel $themeLevel;
    private Question $question;
    private QuestionChoice $questionChoice;
    protected function setUp(): void
    {
        parent::setUp();
        $this->englishLevel = EnglishLevel::factory()->create();
        $this->user = User::factory()->create([
            'english_level_id' => $this->englishLevel->id,
        ]);
        $this->theme = Theme::factory()->create(['name' => 'カフェで注文']);
        $this->themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $this->theme->id,
            'english_level_id' => $this->englishLevel->id,
        ]);
        $this->question = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
        ]);
        $this->questionChoice = QuestionChoice::factory()->create([
            'question_id' => $this->question->id,
        ]);
    }
    public function test_returns_normal_history_card(): void
    {
        $session = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'ended_at' => now()->setDate(2026, 5, 10)->setTime(10, 0, 0),
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories?year_month=2026-05');

        $response->assertStatus(200)
            ->assertJson([
                'history_groups' => [
                    [
                        'year_month' => '2026年5月',
                        'histories' => [
                            [
                                'history_type' => 'normal',
                                'type_label' => '会話練習',
                                'title' => 'カフェで注文',
                                'learned_on' => '5月10日',
                                'study_time' => '10分',
                                'summary' => '2問学習・1問正解',
                            ],
                        ],
                    ],
                ],
            ]);
    }
    public function test_returns_review_history_card(): void
    {
        $reviewSet = ReviewSet::factory()->create([
            'user_id' => $this->user->id,
            'correct_count' => 6,
            'incorrect_count' => 2,
            'skipped_count' => 0,
        ]);
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'review',
            'learning_target_id' => $reviewSet->id,
            'status' => 'completed',
            'duration_seconds' => 360,
            'ended_at' => now()->setDate(2026, 5, 8)->setTime(14, 0, 0),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories?year_month=2026-05');

            $response->assertStatus(200)
            ->assertJson([
                'history_groups' => [
                    [
                        'year_month' => '2026年5月',
                        'histories' => [
                            [
                                'history_type' => 'review',
                                'type_label' => '復習',
                                'title' => '苦手問題集の復習',
                                'learned_on' => '5月8日',
                                'study_time' => '6分',
                                'summary' => '8問復習・6問正解',
                            ],
                        ],
                    ],
                ],
            ]);
    }
    public function test_review_summary_includes_skip_count_when_skipped(): void
    {
        $reviewSet = ReviewSet::factory()->create([
            'user_id' => $this->user->id,
            'correct_count' => 4,
            'incorrect_count' => 2,
            'skipped_count' => 2,
        ]);
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'review',
            'learning_target_id' => $reviewSet->id,
            'status' => 'completed',
            'duration_seconds' => 360,
            'ended_at' => now()->setDate(2026, 5, 8)->setTime(14, 0, 0),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories?year_month=2026-05');

        $response->assertStatus(200)
            ->assertJson([
                'history_groups' => [
                    [
                        'histories' => [
                            [
                                'summary' => '8問復習・4問正解・2問スキップ',
                            ],
                        ],
                    ],
                ],
            ]);
    }
    public function test_groups_same_day_same_theme_sessions_into_one_card(): void
    {
        $session1 = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 300,
            'ended_at' => now()->setDate(2026, 5, 10)->setTime(9, 0, 0),
        ]);
        $session2 = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 300,
            'ended_at' => now()->setDate(2026, 5, 10)->setTime(10, 0, 0),
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session1->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session2->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => false,
        ]);

        $response = $this->actingAs($this->user)
           ->getJson('/api/v1/histories?year_month=2026-05');
       
            $response->assertStatus(200);
        $histories = $response->json('history_groups.0.histories');
        $this->assertCount(1, $histories);
        $this->assertEquals('2問学習・1問正解', $histories[0]['summary']);
        $this->assertEquals('10分', $histories[0]['study_time']);
    }
    public function test_excludes_session_with_less_than_10_seconds_and_no_attempts(): void
    {
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 5,
            'ended_at' => now()->setDate(2026, 5, 10)->setTime(10, 0, 0),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories?year_month=2026-05');

        $response->assertStatus(200)
            ->assertJson(['history_groups' => []]);
    }
    public function test_shows_session_with_less_than_10_seconds_but_has_attempts(): void
    {
        $session = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 5,
            'ended_at' => now()->setDate(2026, 5, 10)->setTime(10, 0, 0),
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories?year_month=2026-05');

        $response->assertStatus(200);
        $histories = $response->json('history_groups.0.histories');
        $this->assertCount(1, $histories);
        $this->assertEquals('1分未満', $histories[0]['study_time']);
    }
    public function test_filters_history_by_specified_year_month(): void
    {
        $sessionCurrentMonth = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'ended_at' => now()->setDate(2026, 5, 10)->setTime(10, 0, 0),
        ]);

        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $sessionCurrentMonth->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);

        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'ended_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories?year_month=2026-05');

        $response->assertStatus(200);

        $groups = $response->json('history_groups');
        $this->assertCount(1, $groups);
        $this->assertEquals('2026年5月', $groups[0]['year_month']);
    }

    public function test_defaults_to_current_month_when_year_month_not_specified(): void
    {
        $sessionCurrentMonth = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'ended_at' => now(),
        ]);

        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $sessionCurrentMonth->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);

        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'ended_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories');

        $response->assertStatus(200);
        $groups = $response->json('history_groups');
        $this->assertCount(1, $groups);
    }
    public function test_returns_study_summary(): void
    {
        $session = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'ended_at' => now(),
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'study_summary' => [
                    'streak_days',
                    'conversation_count',
                    'total_study_time',
                ],
            ])
            ->assertJson([
                'study_summary' => [
                    'streak_days' => 1,
                    'conversation_count' => 1,
                    'total_study_time' => '10分',
                ],
            ]);
    }
    public function test_conversation_count_excludes_review_attempts(): void
    {
        $session = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 600,
            'ended_at' => now(),
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);
        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->questionChoice->id,
            'attempt_type' => 'review',
            'is_correct' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories');

        $response->assertStatus(200)
            ->assertJson([
                'study_summary' => [
                    'conversation_count' => 1,
                ],
            ]);
    }
    public function test_returns_pagination_meta(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories');
        $response->assertStatus(200)
            ->assertJson([
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'last_page' => 1,
                ],
            ]);
    }
    public function test_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/v1/histories');
        $response->assertStatus(401);
    }
    public function test_returns_422_when_year_month_format_is_invalid(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/histories?year_month=2026/05');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year_month']);
    }
}