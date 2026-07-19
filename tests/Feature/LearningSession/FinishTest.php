<?php

namespace Tests\Feature\LearningSession;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinishTest extends TestCase
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
            'started_at' => Carbon::now()->subMinutes(5),
            'last_activity_at' => Carbon::now()->subMinutes(1),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);
    }

    public function test_finishes_session_with_completed(): void
    {
        Carbon::setTestNow(Carbon::now());

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => '学習セッションを終了しました。']);

        $this->session->refresh();
        $this->assertEquals('completed', $this->session->status);
        $this->assertNotNull($this->session->ended_at);
        $this->assertGreaterThan(0, $this->session->duration_seconds);

        Carbon::setTestNow();
    }

    public function test_finishes_session_with_abandoned(): void
    {
        Carbon::setTestNow(Carbon::now());

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'abandoned',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => '学習セッションを終了しました。']);

        $this->session->refresh();
        $this->assertEquals('abandoned', $this->session->status);
        $this->assertNotNull($this->session->ended_at);

        Carbon::setTestNow();
    }

    public function test_caps_duration_at_1800_seconds_for_normal_session(): void
    {
        $this->session->update([
            'started_at' => Carbon::now()->subMinutes(60),
        ]);

        Carbon::setTestNow(Carbon::now());

        $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'completed',
            ]);

        $this->session->refresh();
        $this->assertEquals(1800, $this->session->duration_seconds);

        Carbon::setTestNow();
    }

    public function test_caps_duration_at_3600_seconds_for_review_session(): void
    {
        $this->session->update([
            'learning_target_type' => 'review',
            'started_at' => Carbon::now()->subHours(3),
        ]);

        Carbon::setTestNow(Carbon::now());

        $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'completed',
            ]);

        $this->session->refresh();
        $this->assertEquals(3600, $this->session->duration_seconds);

        Carbon::setTestNow();
    }

    public function test_sets_duration_to_zero_when_less_than_10_seconds(): void
    {
        $this->session->update([
            'started_at' => Carbon::now()->subSeconds(5),
        ]);

        Carbon::setTestNow(Carbon::now());

        $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'abandoned',
            ]);

        $this->session->refresh();
        $this->assertEquals(0, $this->session->duration_seconds);

        Carbon::setTestNow();
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
            'finish_reason' => 'completed',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_404_for_another_users_session(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);

        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'completed',
            ]);

        $response->assertStatus(404)
            ->assertJson(['message' => '学習セッションが見つかりません。']);
    }

    public function test_returns_409_when_session_is_already_completed(): void
    {
        $this->session->update(['status' => 'completed']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'completed',
            ]);

        $response->assertStatus(409)
            ->assertJson(['message' => 'この学習セッションはすでに終了しています。']);
    }

    public function test_returns_409_when_session_is_already_abandoned(): void
    {
        $this->session->update(['status' => 'abandoned']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'completed',
            ]);

        $response->assertStatus(409)
            ->assertJson(['message' => 'この学習セッションはすでに終了しています。']);
    }

    public function test_returns_422_when_finish_reason_is_invalid(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", [
                'finish_reason' => 'invalid_value',
            ]);

        $response->assertStatus(422);
    }

    public function test_returns_422_when_finish_reason_is_missing(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/learning-sessions/{$this->session->id}/finish", []);

        $response->assertStatus(422);
    }
}