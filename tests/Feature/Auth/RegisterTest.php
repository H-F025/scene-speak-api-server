<?php

namespace Tests\Feature\Auth;
use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private EnglishLevel $englishLevel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->englishLevel = EnglishLevel::factory()->create();
    }

    public function test_registers_user_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '山田 太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'english_level' => $this->englishLevel->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'ユーザー登録が完了しました。',
                'user' => [
                    'name' => '山田 太郎',
                    'email' => 'test@example.com',
                    'english_level' => $this->englishLevel->code,
                    'english_level_label' => $this->englishLevel->name,
                ],
            ])
            ->assertJsonStructure([
                'user' => ['user_id', 'name', 'email', 'english_level', 'english_level_label'],
            ]);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => '山田 太郎',
            'english_level_id' => $this->englishLevel->id,
        ]);
    }

    public function test_returns_409_when_email_already_exists(): void
    {
        User::create([
            'name' => '既存ユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'english_level_id' => $this->englishLevel->id,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '山田 太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'english_level' => $this->englishLevel->id,
        ]);
        
        $response->assertStatus(409)
            ->assertJson(['message' => 'メールアドレスがすでに登録されています。']);
    }
}
