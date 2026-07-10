<?php

namespace Tests\Feature\LearningSession;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HeartbeatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LearningSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $englishLevel = EnglishLevel::factory()->create(['code' => 'beginner', 'name' => '初級', 'sort_order' => 1]);
        $this->user = User::factory()->create(['english_level_id' => $englishLevel->id]);

        $this->session = LearningSession::create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'normal',
            'learning_target_id' => 1,
            'status' => 'in_progress',
            'started_at' => Carbon::now()->subMinutes(2),
            'last_activity_at' => Carbon::now()->subSeconds(30),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);
    }

    public function test_updates_last_activity_at_and_returns_session_id(): void
    {
        Carbon::setTestNow(Carbon::now());

        $before = Carbon::now()->subSeconds(5);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/heartbeat");

        $response->assertStatus(200)
            ->assertJson(['learning_session_id' => $this->session->id]);

        $this->session->refresh();
        $this->assertEquals('in_progress', $this->session->status);
        $this->assertTrue(
            Carbon::parse($this->session->last_activity_at)->greaterThan($before)
        );

        Carbon::setTestNow();
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson("/api/v1/learning-sessions/{$this->session->id}/heartbeat");

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_404_for_another_users_session(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);

        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/heartbeat");

        $response->assertStatus(404)
            ->assertJson(['message' => '学習セッションが見つかりません。']);
    }

    public function test_returns_409_when_session_is_completed(): void
    {
        $this->session->update(['status' => 'completed']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/heartbeat");

        $response->assertStatus(409)
            ->assertJson(['message' => 'この学習セッションはすでに終了しています。']);
    }

    public function test_returns_409_when_session_is_interrupted(): void
    {
        $this->session->update(['status' => 'interrupted']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/heartbeat");

        $response->assertStatus(409)
            ->assertJson(['message' => 'この学習セッションはすでに終了しています。']);
    }

    public function test_returns_409_when_session_is_abandoned(): void
    {
        $this->session->update(['status' => 'abandoned']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/heartbeat");

        $response->assertStatus(409)
            ->assertJson(['message' => 'この学習セッションはすでに終了しています。']);
    }
}