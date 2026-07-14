<?php

namespace Tests\Feature\ReviewSet\Question;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionChoice;
use App\Models\ReviewSet;
use App\Models\ReviewSetQuestion;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ReviewSet $reviewSet;
    private ReviewSetQuestion $reviewSetQuestion;
    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $englishLevel = EnglishLevel::factory()->create();
        $theme = Theme::factory()->create();
        $themeLevel = ThemeLevel::factory()->create([
            'theme_id' => $theme->id,
            'english_level_id' => $englishLevel->id,
        ]);
        $this->user = User::factory()->create(['english_level_id' => $englishLevel->id]);

        $this->question = Question::factory()->create(['theme_level_id' => $themeLevel->id]);
        QuestionChoice::factory()->create(['question_id' => $this->question->id, 'sort_order' => 1]);

        $this->reviewSet = ReviewSet::factory()->create([
            'user_id' => $this->user->id,
            'target_question_count' => 3,
        ]);

        $this->reviewSetQuestion = ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $this->question->id,
            'order_no' => 1,
            'result' => 'not_answered',
        ]);
    }

    private function getUrl(?int $reviewSetId = null, ?int $reviewSetQuestionId = null): string
    {
        $reviewSetId ??= $this->reviewSet->id;
        $reviewSetQuestionId ??= $this->reviewSetQuestion->id;

        return "/api/v1/review-sets/{$reviewSetId}/questions/{$reviewSetQuestionId}";
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson($this->getUrl());

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_200_with_correct_response_structure(): void
    {
        $category = QuestionCategory::factory()->create();
        $this->question->categories()->attach($category->id);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'review_set_id',
                'category_name',
                'progress' => [
                    'current_question_number',
                    'total_question_count',
                    'completed_question_count',
                    'remaining_question_count',
                ],
                'question' => [
                    'id',
                    'title',
                    'scene_label',
                    'partner_message',
                    'instruction',
                    'question_text',
                    'hint',
                    'choices' => [
                        '*' => ['id', 'content'],
                    ],
                ],
            ]);
    }

    public function test_returns_correct_values(): void
    {
        $category = QuestionCategory::factory()->create(['name' => 'テストカテゴリ']);
        $this->question->categories()->attach($category->id);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJson([
                'review_set_id' => $this->reviewSet->id,
                'category_name' => 'テストカテゴリ',
                'progress' => [
                    'current_question_number' => 1,
                    'total_question_count' => 3,
                    'completed_question_count' => 0,
                    'remaining_question_count' => 3,
                ],
                'question' => [
                    'id' => $this->question->id,
                    'title' => $this->question->title,
                    'question_text' => $this->question->question,
                ],
            ]);
    }

    public function test_returns_404_when_review_set_not_found(): void
    {
        $response = $this->actingAs($this->user)->getJson($this->getUrl(reviewSetId: 9999));

        $response->assertStatus(404)
            ->assertJson(['message' => '復習問題が見つかりません。']);
    }

    public function test_returns_404_when_review_set_belongs_to_other_user(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);
        $otherReviewSet = ReviewSet::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl(reviewSetId: $otherReviewSet->id));

        $response->assertStatus(404)
            ->assertJson(['message' => '復習問題が見つかりません。']);
    }

    public function test_returns_404_when_review_set_question_not_found(): void
    {
        $response = $this->actingAs($this->user)->getJson($this->getUrl(reviewSetQuestionId: 9999));

        $response->assertStatus(404)
            ->assertJson(['message' => '復習問題が見つかりません。']);
    }

    public function test_returns_404_when_review_set_question_belongs_to_other_review_set(): void
    {
        $otherReviewSet = ReviewSet::factory()->create(['user_id' => $this->user->id]);
        $otherReviewSetQuestion = ReviewSetQuestion::factory()->create([
            'review_set_id' => $otherReviewSet->id,
            'question_id' => $this->question->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            $this->getUrl(reviewSetQuestionId: $otherReviewSetQuestion->id)
        );

        $response->assertStatus(404)
            ->assertJson(['message' => '復習問題が見つかりません。']);
    }

    public function test_progress_counts_answered_questions_correctly(): void
    {
        $question2 = Question::factory()->create(['theme_level_id' => $this->question->theme_level_id]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question2->id,
            'order_no' => 2,
            'result' => 'correct',
        ]);

        $question3 = Question::factory()->create(['theme_level_id' => $this->question->theme_level_id]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question3->id,
            'order_no' => 3,
            'result' => 'incorrect',
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJson([
                'progress' => [
                    'total_question_count' => 3,
                    'completed_question_count' => 2,
                    'remaining_question_count' => 1,
                ],
            ]);
    }

    public function test_updates_last_activity_at_when_learning_session_is_in_progress(): void
    {
        $pastTime = now()->subMinutes(10)->toDateTimeString();

        $learningSession = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'review',
            'learning_target_id' => $this->reviewSet->id,
            'status' => 'in_progress',
            'last_activity_at' => $pastTime,
        ]);

        $this->actingAs($this->user)->getJson($this->getUrl());

        $learningSession->refresh();
        $this->assertNotEquals($pastTime, $learningSession->last_activity_at);
    }

    public function test_does_not_update_last_activity_at_when_no_in_progress_learning_session(): void
    {
        $pastTime = now()->subMinutes(10)->toDateTimeString();

        $learningSession = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'review',
            'learning_target_id' => $this->reviewSet->id,
            'status' => 'completed',
            'last_activity_at' => $pastTime,
        ]);

        $this->actingAs($this->user)->getJson($this->getUrl());

        $learningSession->refresh();
        $this->assertEquals($pastTime, $learningSession->last_activity_at);
    }

    public function test_choices_are_ordered_by_sort_order(): void
    {
        $question = Question::factory()->create(['theme_level_id' => $this->question->theme_level_id]);
        QuestionChoice::factory()->create(['question_id' => $question->id, 'content' => '3番目', 'sort_order' => 3]);
        QuestionChoice::factory()->create(['question_id' => $question->id, 'content' => '1番目', 'sort_order' => 1]);
        QuestionChoice::factory()->create(['question_id' => $question->id, 'content' => '2番目', 'sort_order' => 2]);

        $reviewSetQuestion = ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question->id,
            'order_no' => 2,
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl(reviewSetQuestionId: $reviewSetQuestion->id));

        $response->assertStatus(200);
        $choices = $response->json('question.choices');
        $this->assertEquals('1番目', $choices[0]['content']);
        $this->assertEquals('2番目', $choices[1]['content']);
        $this->assertEquals('3番目', $choices[2]['content']);
    }
}