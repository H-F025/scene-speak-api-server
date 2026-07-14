<?php
namespace Tests\Feature\ReviewSet;

use App\Models\EnglishLevel;
use App\Models\Question;
use App\Models\ReviewQuestionState;
use App\Models\ReviewSet;
use App\Models\ReviewSetQuestion;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
class StoreTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private ThemeLevel $themeLevel;
    protected function setUp(): void
    {
        parent::setUp();
        $englishLevel = EnglishLevel::factory()->create();
        $theme = Theme::factory()->create();
        $this->themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $englishLevel->id,
        ]);
        $this->user = User::factory()->create(['english_level_id' => $englishLevel->id]);
    }
    private function createReviewSet(): TestResponse
    {
        return $this->actingAs($this->user)->postJson('/api/v1/review-sets');
    }
    private function createReviewQuestionState(array $attributes = []): ReviewQuestionState
    {
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        return ReviewQuestionState::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
        ], $attributes));
    }
    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/review-sets');
        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }
    public function test_returns_409_when_no_review_questions(): void
    {
        $response = $this->createReviewSet();
        $response->assertStatus(409)
            ->assertJson(['message' => '現在、復習できる問題がありません。']);
    }
    public function test_creates_review_set_and_returns_201(): void
    {
        $this->createReviewQuestionState();
        $response = $this->createReviewSet();
        $response->assertStatus(201)
            ->assertJsonStructure([
                'review_set_id',
                'first_review_set_question_id',
            ]);
        $this->assertDatabaseCount('review_sets', 1);
        $this->assertDatabaseCount('review_set_questions', 1);
    }
    public function test_review_set_is_created_with_correct_values(): void
    {
        $this->createReviewQuestionState();
        $response = $this->createReviewSet();
        $reviewSetId = $response->json('review_set_id');
        $this->assertDatabaseHas('review_sets', [
            'id' => $reviewSetId,
            'user_id' => $this->user->id,
            'status' => 'created',
            'target_question_count' => 1,
            'priority' => 'low',
            'estimated_seconds' => 45,
            'correct_count' => 0,
            'incorrect_count' => 0,
            'skipped_count' => 0,
        ]);
    }
    public function test_review_set_question_is_created_with_correct_values(): void
    {
        $state = $this->createReviewQuestionState();
        $response = $this->createReviewSet();
        $firstReviewSetQuestionId = $response->json('first_review_set_question_id');
        $this->assertDatabaseHas('review_set_questions', [
            'id' => $firstReviewSetQuestionId,
            'question_id' => $state->question_id,
            'question_attempt_id' => null,
            'order_no' => 1,
            'result' => 'not_answered',
            'answered_at' => null,
        ]);
    }
    public function test_returns_first_review_set_question_id(): void
    {
        // 2件作成して、より新しいものが1問目になることを確認する
        $this->createReviewQuestionState(['updated_at' => now()->subHour()]);
        $newerState = $this->createReviewQuestionState(['updated_at' => now()]);
        $response = $this->createReviewSet();
        $response->assertStatus(201);
        $firstReviewSetQuestionId = $response->json('first_review_set_question_id');
        $firstReviewSetQuestion = ReviewSetQuestion::find($firstReviewSetQuestionId);
        $this->assertEquals($newerState->question_id, $firstReviewSetQuestion->question_id);
        $this->assertEquals(1, $firstReviewSetQuestion->order_no);
    }
    public function test_creates_multiple_review_set_questions_with_order(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->createReviewQuestionState();
        }
        $response = $this->createReviewSet();
        $response->assertStatus(201);
        $reviewSetId = $response->json('review_set_id');
        $reviewSet = ReviewSet::find($reviewSetId);
        $this->assertDatabaseCount('review_set_questions', 3);
        $this->assertEquals(3, $reviewSet->reviewSetQuestions()->count());
        $orderNos = $reviewSet->reviewSetQuestions()->pluck('order_no')->sort()->values()->toArray();
        $this->assertEquals([1, 2, 3], $orderNos);
    }
    public function test_max_8_questions_are_included(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->createReviewQuestionState();
        }
        $response = $this->createReviewSet();
        $response->assertStatus(201);
        $this->assertDatabaseCount('review_set_questions', 8);
        $reviewSetId = $response->json('review_set_id');
        $this->assertDatabaseHas('review_sets', [
            'id' => $reviewSetId,
            'target_question_count' => 8,
        ]);
    }
    public function test_excludes_resolved_questions(): void
    {
        $this->createReviewQuestionState(['status' => 'resolved']);
        $response = $this->createReviewSet();
        $response->assertStatus(409);
    }
    public function test_excludes_questions_older_than_7_days(): void
    {
        $this->createReviewQuestionState([
            'updated_at' => now()->subDays(7)->startOfDay()->subSecond(),
        ]);
        $response = $this->createReviewSet();
        $response->assertStatus(409);
    }
    public function test_does_not_include_other_users_questions(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        ReviewQuestionState::factory()->create([
            'user_id' => $otherUser->id,
            'question_id' => $question->id,
        ]);
        $response = $this->createReviewSet();
        $response->assertStatus(409);
    }
    public function test_priority_is_medium_for_3_to_5_questions(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->createReviewQuestionState();
        }
        $response = $this->createReviewSet();
        $response->assertStatus(201);
        $reviewSetId = $response->json('review_set_id');
        $this->assertDatabaseHas('review_sets', [
            'id' => $reviewSetId,
            'priority' => 'medium',
            'estimated_seconds' => 180,
        ]);
    }
    public function test_priority_is_high_for_6_or_more_questions(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->createReviewQuestionState();
        }
        $response = $this->createReviewSet();
        $response->assertStatus(201);
        $reviewSetId = $response->json('review_set_id');
        $this->assertDatabaseHas('review_sets', [
            'id' => $reviewSetId,
            'priority' => 'high',
            'estimated_seconds' => 270,
        ]);
    }
}
