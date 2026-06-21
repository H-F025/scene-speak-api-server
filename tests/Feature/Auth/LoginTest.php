<?php

namespace Tests\Feature\Auth;

use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private EnglishLevel $englishLevel;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->englishLevel = EnglishLevel::factory()->create();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'english_level_id' => $this->englishLevel->id,
        ]);
    }

    public function test_logs_in_successfully(): void
    {
        $response = $this->withHeader('Origin', 'http://localhost')
            ->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'ログインしました。',
                'user' => [
                    'user_id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => 'test@example.com',
                    'english_level' => $this->englishLevel->code,
                    'english_level_label' => $this->englishLevel->name,
                ],
            ])
            ->assertJsonStructure([
                'user' => ['user_id', 'name', 'email', 'english_level', 'english_level_label'],
            ]);
    }
    public function test_returns_401_when_email_does_not_exist(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'notfound@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'メールアドレスまたはパスワードが正しくありません。']);
    }

    public function test_returns_401_when_password_is_wrong(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'メールアドレスまたはパスワードが正しくありません。']);
    }
}