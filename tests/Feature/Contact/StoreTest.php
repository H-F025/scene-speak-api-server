<?php

namespace Tests\Feature\Contact;

use App\Mail\ContactReceived;
use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private array $validPayload = [
        'name'    => 'テスト太郎',
        'email'   => 'test@example.com',
        'message' => 'お問い合わせ内容のテストです。',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $englishLevel = EnglishLevel::factory()->create();
        $this->user = User::factory()->create([
            'english_level_id' => $englishLevel->id,
        ]);
    }

    public function test_ログインユーザーがお問い合わせを送信できる(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/contacts', $this->validPayload);

        $response->assertCreated()
            ->assertJson(['message' => 'お問い合わせを受け付けました。']);

        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'name'    => 'テスト太郎',
            'email'   => 'test@example.com',
        ]);
    }

    public function test_未ログインの場合は401が返る(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/contacts', $this->validPayload)
            ->assertUnauthorized();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_運営者宛にメールが送信される(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/contacts', $this->validPayload)
            ->assertCreated();

        Mail::assertSent(ContactReceived::class, function (ContactReceived $mail) {
            return $mail->hasTo(config('mail.contact_to'))
                && $mail->contact->email === 'test@example.com';
        });
    }

    public function test_送信者のアドレスがreplyToに設定される(): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/contacts', $this->validPayload)
            ->assertCreated();

        Mail::assertSent(ContactReceived::class, function (ContactReceived $mail) {
            return $mail->hasReplyTo('test@example.com');
        });
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_バリデーションエラーになる(array $overrides, string $errorKey): void
    {
        Mail::fake();

        $this->actingAs($this->user)
            ->postJson('/api/v1/contacts', array_merge($this->validPayload, $overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errorKey);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('contacts', 0);
    }

    public static function invalidPayloadProvider(): array
    {
        return [
            '名前が未入力'           => [['name' => ''], 'name'],
            '名前が51文字'           => [['name' => str_repeat('あ', 51)], 'name'],
            'メールアドレスが未入力' => [['email' => ''], 'email'],
            'メールアドレスが不正'   => [['email' => 'invalid-email'], 'email'],
            '内容が未入力'           => [['message' => ''], 'message'],
            '内容が2001文字'         => [['message' => str_repeat('あ', 2001)], 'message'],
        ];
    }

    public function test_リクエスト制限を超えると429が返る(): void
    {
        Mail::fake();
        $this->app['cache']->clear();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->user)
                ->postJson('/api/v1/contacts', $this->validPayload)
                ->assertCreated();
        }

        $this->actingAs($this->user)
            ->postJson('/api/v1/contacts', $this->validPayload)
            ->assertStatus(429);

        $this->assertDatabaseCount('contacts', 5);
    }

    public function test_メール送信に失敗しても受付は成功する(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('送信失敗'));

        $this->actingAs($this->user)
            ->postJson('/api/v1/contacts', $this->validPayload)
            ->assertCreated();

        $this->assertDatabaseCount('contacts', 1);
    }
}