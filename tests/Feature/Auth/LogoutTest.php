<?php

namespace Tests\Feature\Auth;

use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $englishLevel = EnglishLevel::factory()->create();

        $this->user = User::factory()->create([
            'english_level_id' => $englishLevel->id,
        ]);
    }

    public function test_logs_out_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('Origin', 'http://localhost')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'ログアウトしました。']);
    }

    public function test_returns_401_when_not_authenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}