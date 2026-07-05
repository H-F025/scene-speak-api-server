<?php

namespace Tests\Feature\LearningSession;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StartTest extends TestCase
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

    public function test_creates_normal_learning_session(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/learning-sessions', [
                'learning_type' => 'normal',
                'learning_target_id' => $this->themeLevel->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['learning_session_id']);

        $this->assertDatabaseHas('learning_sessions', [
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'in_progress',
            'duration_seconds' => 0,
        ]);

        $sessionId = $response->json('learning_session_id');
        $session = LearningSession::find($sessionId);
        $this->assertNotNull($session->started_at);
        $this->assertNotNull($session->last_activity_at);
        $this->assertNull($session->ended_at);
    }

    public function test_creates_review_learning_session(): void
    {
        $dummyReviewSetId = 1;

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/learning-sessions', [
                'learning_type' => 'review',
                'learning_target_id' => $dummyReviewSetId,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['learning_session_id']);

        $this->assertDatabaseHas('learning_sessions', [
            'user_id' => $this->user->id,
            'learning_target_type' => 'review',
            'learning_target_id' => $dummyReviewSetId,
            'status' => 'in_progress',
            'duration_seconds' => 0,
        ]);
    }

    public function test_auto_closes_stale_in_progress_sessions_before_creating(): void
    {
        Carbon::setTestNow(Carbon::now());

        $startedAt = Carbon::now()->subMinutes(10);
        $lastActivityAt = Carbon::now()->subMinutes(6);

        $staleSession = LearningSession::create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'in_progress',
            'started_at' => $startedAt,
            'last_activity_at' => $lastActivityAt,
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/learning-sessions', [
                'learning_type' => 'normal',
                'learning_target_id' => $this->themeLevel->id,
            ]);

        $staleSession->refresh();
        $this->assertEquals('interrupted', $staleSession->status);
        $this->assertEquals($lastActivityAt->toDateTimeString(), $staleSession->ended_at);
        $this->assertEquals(
            min($startedAt->diffInSeconds($lastActivityAt), 1800),
            $staleSession->duration_seconds
        );

        Carbon::setTestNow();
    }

    public function test_does_not_auto_close_recent_in_progress_sessions(): void
    {
        $recentSession = LearningSession::create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'in_progress',
            'started_at' => Carbon::now()->subMinutes(2),
            'last_activity_at' => Carbon::now()->subMinutes(2),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/learning-sessions', [
                'learning_type' => 'normal',
                'learning_target_id' => $this->themeLevel->id,
            ]);

        $recentSession->refresh();
        $this->assertEquals('in_progress', $recentSession->status);
    }

    public function test_does_not_auto_close_other_users_stale_sessions(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);

        $otherSession = LearningSession::create([
            'user_id' => $otherUser->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
            'status' => 'in_progress',
            'started_at' => Carbon::now()->subMinutes(10),
            'last_activity_at' => Carbon::now()->subMinutes(6),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/learning-sessions', [
                'learning_type' => 'normal',
                'learning_target_id' => $this->themeLevel->id,
            ]);

        $otherSession->refresh();
        $this->assertEquals('in_progress', $otherSession->status);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/learning-sessions', [
            'learning_type' => 'normal',
            'learning_target_id' => $this->themeLevel->id,
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }
}