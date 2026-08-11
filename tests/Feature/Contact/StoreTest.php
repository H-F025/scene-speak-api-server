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

    private EnglishLevel $englishLevel;

    private array $validPayload = [
        "name"    => "テスト太郎",
        "email"   => "test@example.com",
        "subject" => "お問い合わせテスト",
        "body"    => "お問い合わせ内容のテストです。",
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->englishLevel = EnglishLevel::factory()->create();
    }

    public function test_未ログインでもお問い合わせを送信できる(): void
    {
        Mail::fake();

        $response = $this->postJson("/api/v1/contacts", $this->validPayload);

        $response->assertCreated()
            ->assertJson(["message" => "お問い合わせを受け付けました。"]);

        $this->assertDatabaseHas("contacts", [
            "user_id" => null,
            "name"    => "テスト太郎",
            "email"   => "test@example.com",
            "subject" => "お問い合わせテスト",
        ]);
    }

    public function test_運営者宛にメールが送信される(): void
    {
        Mail::fake();

        $this->postJson("/api/v1/contacts", $this->validPayload)->assertCreated();

        Mail::assertSent(ContactReceived::class, function (ContactReceived $mail) {
            return $mail->hasTo(config("mail.contact_to"))
                && $mail->contact->email === "test@example.com";
        });
    }

    public function test_送信者のアドレスがreplyToに設定される(): void
    {
        Mail::fake();

        $this->postJson("/api/v1/contacts", $this->validPayload)->assertCreated();

        Mail::assertSent(ContactReceived::class, function (ContactReceived $mail) {
            return $mail->hasReplyTo("test@example.com");
        });
    }

    public function test_ログイン中の場合はユーザーIDが紐づく(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            "english_level_id" => $this->englishLevel->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/contacts", $this->validPayload)
            ->assertCreated();

        $this->assertDatabaseHas("contacts", [
            "user_id" => $user->id,
        ]);
    }

    #[DataProvider("invalidPayloadProvider")]
    public function test_バリデーションエラーになる(array $overrides, string $errorKey): void
    {
        Mail::fake();

        $response = $this->postJson("/api/v1/contacts", array_merge($this->validPayload, $overrides));

        $response->assertUnprocessable()->assertJsonValidationErrors($errorKey);

        Mail::assertNothingSent();
        $this->assertDatabaseCount("contacts", 0);
    }

    public static function invalidPayloadProvider(): array
    {
        return [
            "名前が未入力"           => [["name" => ""], "name"],
            "名前が51文字"           => [["name" => str_repeat("あ", 51)], "name"],
            "メールアドレスが未入力" => [["email" => ""], "email"],
            "メールアドレスが不正"   => [["email" => "invalid-email"], "email"],
            "件名が未入力"           => [["subject" => ""], "subject"],
            "件名が101文字"          => [["subject" => str_repeat("あ", 101)], "subject"],
            "本文が未入力"           => [["body" => ""], "body"],
            "本文が2001文字"         => [["body" => str_repeat("あ", 2001)], "body"],
        ];
    }

    public function test_リクエスト制限を超えると429が返る(): void
    {
        Mail::fake();
        $this->app["cache"]->clear();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/contacts", $this->validPayload)->assertCreated();
        }

        $this->postJson("/api/v1/contacts", $this->validPayload)->assertStatus(429);

        $this->assertDatabaseCount("contacts", 5);
    }

    public function test_メール送信に失敗しても受付は成功する(): void
    {
        Mail::shouldReceive("to->send")->andThrow(new \RuntimeException("送信失敗"));

        $this->postJson("/api/v1/contacts", $this->validPayload)->assertCreated();

        $this->assertDatabaseCount("contacts", 1);
    }
}