<?php

namespace Tests\Feature\EnglishLevel;

use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private EnglishLevel $beginnerLevel;

    private EnglishLevel $intermediateLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->beginnerLevel = EnglishLevel::factory()->create(['code' => 'beginner', 'sort_order' => 1]);
        $this->intermediateLevel = EnglishLevel::factory()->create(['code' => 'intermediate', 'sort_order' => 2]);

        $this->user = User::factory()->create([
            'english_level_id' => $this->beginnerLevel->id,
        ]);
    }

    public function test_updates_english_level_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/me/english-level', ['id' => $this->intermediateLevel->id]);

        $response->assertStatus(200)
            ->assertJson(['message' => '英語レベルを更新しました。']);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'english_level_id' => $this->intermediateLevel->id,
        ]);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->patchJson('/api/v1/me/english-level', ['id' => $this->intermediateLevel->id]);

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_404_when_english_level_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/v1/me/english-level', ['id' => 9999]);

        $response->assertStatus(404)
            ->assertJson(['message' => '英語レベルが見つかりません。']);
    }
}