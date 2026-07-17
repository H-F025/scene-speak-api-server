<?php

namespace Tests\Feature\ReviewSet;

use App\Models\EnglishLevel;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\ReviewQuestionState;
use App\Models\ReviewSet;
use App\Models\ReviewSetQuestion;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ReviewSet $reviewSet;

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

        $this->reviewSet = ReviewSet::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'target_question_count' => 3,
            'correct_count' => 2,
            'incorrect_count' => 1,
            'skipped_count' => 0,
        ]);
    }

    private function getUrl(?int $reviewSetId = null): string
    {
        $reviewSetId ??= $this->reviewSet->id;

        return "/api/v1/review-sets/{$reviewSetId}/completion";
    }

    public function test_returns_200_with_completion_summary(): void
    {
        $question1 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question2 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question3 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);

        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question1->id,
            'order_no' => 1,
            'result' => 'correct',
        ]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question2->id,
            'order_no' => 2,
            'result' => 'incorrect',
        ]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question3->id,
            'order_no' => 3,
            'result' => 'correct',
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'result' => [
                    'total_question_count',
                    'correct_count',
                ],
                'reviewed_categories',
                'reviewed_category_count',
                'next_recommendation_type',
            ])
            ->assertJsonPath('result.total_question_count', 3)
            ->assertJsonPath('result.correct_count', 2);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson($this->getUrl());

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_404_when_review_set_not_found(): void
    {
        $response = $this->actingAs($this->user)->getJson($this->getUrl(9999));

        $response->assertStatus(404)
            ->assertJson(['message' => '復習セットが見つかりません。']);
    }

    public function test_returns_404_when_review_set_belongs_to_other_user(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);
        $otherReviewSet = ReviewSet::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl($otherReviewSet->id));

        $response->assertStatus(404)
            ->assertJson(['message' => '復習セットが見つかりません。']);
    }

    public function test_returns_409_when_review_set_is_not_completed(): void
    {
        $this->reviewSet->update(['status' => 'in_progress']);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(409)
            ->assertJson(['message' => '復習セットがまだ完了していません。']);
    }

    public function test_next_recommendation_type_is_review_skipped_when_skipped_count_is_positive(): void
    {
        $this->reviewSet->update(['skipped_count' => 1]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonPath('next_recommendation_type', 'review_skipped');
    }

    public function test_next_recommendation_type_is_review_remaining_when_needs_review_exists(): void
    {
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);

        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'status' => 'needs_review',
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonPath('next_recommendation_type', 'review_remaining');
    }

    public function test_next_recommendation_type_is_review_completed_when_no_review_remains(): void
    {
        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonPath('next_recommendation_type', 'review_completed');
    }

    public function test_reviewed_categories_excludes_skipped_and_not_answered(): void
    {
        $question1 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question2 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $category = QuestionCategory::factory()->create(['name' => '時制・過去形', 'sort_order' => 1]);
        $question1->categories()->attach($category->id);

        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question1->id,
            'order_no' => 1,
            'result' => 'correct',
        ]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question2->id,
            'order_no' => 2,
            'result' => 'skipped',
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonPath('reviewed_category_count', 1)
            ->assertJsonPath('reviewed_categories', ['時制・過去形']);
    }

    public function test_reviewed_categories_returns_at_most_two_items(): void
    {
        $category1 = QuestionCategory::factory()->create(['name' => 'カテゴリA', 'sort_order' => 1]);
        $category2 = QuestionCategory::factory()->create(['name' => 'カテゴリB', 'sort_order' => 2]);
        $category3 = QuestionCategory::factory()->create(['name' => 'カテゴリC', 'sort_order' => 3]);

        foreach ([$category1, $category2, $category3] as $index => $category) {
            $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
            $question->categories()->attach($category->id);
            ReviewSetQuestion::factory()->create([
                'review_set_id' => $this->reviewSet->id,
                'question_id' => $question->id,
                'order_no' => $index + 1,
                'result' => 'correct',
            ]);
        }

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonCount(2, 'reviewed_categories')
            ->assertJsonPath('reviewed_category_count', 3);
    }

    public function test_reviewed_categories_are_sorted_by_question_count_then_sort_order(): void
    {
        $category1 = QuestionCategory::factory()->create(['name' => 'カテゴリA', 'sort_order' => 1]);
        $category2 = QuestionCategory::factory()->create(['name' => 'カテゴリB', 'sort_order' => 2]);

        // category2 に2問、category1 に1問割り当てる
        $question1 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question1->categories()->attach($category2->id);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question1->id,
            'order_no' => 1,
            'result' => 'correct',
        ]);

        $question2 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question2->categories()->attach($category2->id);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question2->id,
            'order_no' => 2,
            'result' => 'correct',
        ]);

        $question3 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question3->categories()->attach($category1->id);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question3->id,
            'order_no' => 3,
            'result' => 'incorrect',
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonPath('reviewed_categories.0', 'カテゴリB')
            ->assertJsonPath('reviewed_categories.1', 'カテゴリA');
    }

    public function test_reviewed_categories_is_empty_when_all_skipped(): void
    {
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question->id,
            'order_no' => 1,
            'result' => 'skipped',
        ]);

        $response = $this->actingAs($this->user)->getJson($this->getUrl());

        $response->assertStatus(200)
            ->assertJsonPath('reviewed_categories', [])
            ->assertJsonPath('reviewed_category_count', 0);
    }
}