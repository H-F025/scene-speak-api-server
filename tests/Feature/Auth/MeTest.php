<?php

namespace Tests\Feature\Auth;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\QuestionChoice;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
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

    public function test_returns_user_info_and_study_summary(): void
    {
        $theme = Theme::factory()->create();
        $themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $this->englishLevel->id,
        ]);
        $question = Question::factory()->create(['theme_level_id' => $themeLevel->id]);
        $choice = QuestionChoice::factory()->create(['question_id' => $question->id]);

        $session1 = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $themeLevel->id,
            'status' => 'completed',
            'duration_seconds' => 300,
            'started_at' => now(),
        ]);

        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $themeLevel->id,
            'status' => 'abandoned',
            'duration_seconds' => 120,
            'started_at' => now()->subDays(1),
        ]);

        QuestionAttempt::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session1->id,
            'question_id' => $question->id,
            'question_choice_id' => $choice->id,
            'attempt_type' => 'theme',
        ]);

        QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $session1->id,
            'question_id' => $question->id,
            'question_choice_id' => $choice->id,
            'attempt_type' => 'review',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'english_level' => $this->englishLevel->code,
                    'english_level_label' => $this->englishLevel->name,
                ],
                'study_summary' => [
                    'consecutive_days' => 2,
                    'conversation_count' => 3,
                    'total_study_seconds' => 420,
                    'total_study_time_label' => '7分',
                ],
            ]);
    }

    public function test_returns_zeros_when_no_study_data(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'study_summary' => [
                    'consecutive_days' => 0,
                    'conversation_count' => 0,
                    'total_study_seconds' => 0,
                    'total_study_time_label' => '0分',
                ],
            ]);
    }

    public function test_excludes_in_progress_sessions_from_totals(): void
    {
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => 1,
            'status' => 'in_progress',
            'duration_seconds' => 600,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'study_summary' => [
                    'consecutive_days' => 0,
                    'total_study_seconds' => 0,
                ],
            ]);
    }

    public function test_excludes_zero_duration_sessions_from_totals(): void
    {
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => 1,
            'status' => 'completed',
            'duration_seconds' => 0,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'study_summary' => [
                    'consecutive_days' => 0,
                    'total_study_seconds' => 0,
                ],
            ]);
    }

    public function test_consecutive_days_maintained_when_studied_yesterday_but_not_today(): void
    {
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => 1,
            'status' => 'completed',
            'duration_seconds' => 300,
            'started_at' => now()->subDays(1),
        ]);

        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => 1,
            'status' => 'completed',
            'duration_seconds' => 300,
            'started_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'study_summary' => [
                    'consecutive_days' => 2,
                ],
            ]);
    }

    public function test_consecutive_days_resets_when_gap_exists(): void
    {
        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => 1,
            'status' => 'completed',
            'duration_seconds' => 300,
            'started_at' => now(),
        ]);

        LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => 1,
            'status' => 'completed',
            'duration_seconds' => 300,
            'started_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'study_summary' => [
                    'consecutive_days' => 1,
                ],
            ]);
    }

    public function test_returns_401_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
