<?php

namespace Tests\Feature\ReviewSet;

use App\Models\EnglishLevel;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\ReviewQuestionState;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
class IndexTest extends TestCase
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
    private function getReviewSet(): TestResponse
    {
        return $this->actingAs($this->user)->getJson('/api/v1/review-sets');
    }
    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/review-sets');
        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }
    public function test_returns_non_priority_when_no_review_questions(): void
    {
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 0,
                'priority' => 'non',
                'priority_label' => null,
                'estimated_seconds' => 0,
                'estimated_minutes' => 0,
                'categories' => [],
            ]);
    }
    public function test_returns_low_priority_for_1_to_2_questions(): void
    {
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
        ]);
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 1,
                'priority' => 'low',
                'priority_label' => '低',
                'estimated_seconds' => 45,
                'estimated_minutes' => 1,
            ]);
    }
    public function test_returns_medium_priority_for_3_to_5_questions(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
            ReviewQuestionState::factory()->create([
                'user_id' => $this->user->id,
                'question_id' => $question->id,
            ]);
        }
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 3,
                'priority' => 'medium',
                'priority_label' => '中',
                'estimated_seconds' => 135,
                'estimated_minutes' => 3,
            ]);
    }
    public function test_returns_high_priority_for_6_or_more_questions(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
            ReviewQuestionState::factory()->create([
                'user_id' => $this->user->id,
                'question_id' => $question->id,
            ]);
        }
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 6,
                'priority' => 'high',
                'priority_label' => '高',
                'estimated_seconds' => 270,
                'estimated_minutes' => 5,
            ]);
    }
    public function test_max_8_questions_are_counted(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
            ReviewQuestionState::factory()->create([
                'user_id' => $this->user->id,
                'question_id' => $question->id,
            ]);
        }
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 8,
                'priority' => 'high',
                'estimated_seconds' => 360,
                'estimated_minutes' => 6,
            ]);
    }
    public function test_excludes_questions_older_than_7_days(): void
    {
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'updated_at' => now()->subDays(7)->startOfDay()->subSecond(),
        ]);
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 0,
                'priority' => 'non',
            ]);
    }
    public function test_excludes_resolved_questions(): void
    {
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'status' => 'resolved',
        ]);
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 0,
                'priority' => 'non',
            ]);
    }
    public function test_returns_categories_with_question_counts(): void
    {
        $category1 = QuestionCategory::factory()->create(['sort_order' => 1]);
        $category2 = QuestionCategory::factory()->create(['sort_order' => 2]);
        // category1 に2問、category2 に1問紐づける
        $question1 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question1->categories()->attach($category1->id);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question1->id,
        ]);
        $question2 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question2->categories()->attach($category1->id);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question2->id,
        ]);
        $question3 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question3->categories()->attach($category2->id);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question3->id,
        ]);
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 3,
                'categories' => [
                    [
                        'id' => $category1->id,
                        'name' => $category1->name,
                        'description' => $category1->description,
                        'question_count' => 2,
                    ],
                    [
                        'id' => $category2->id,
                        'name' => $category2->name,
                        'description' => $category2->description,
                        'question_count' => 1,
                    ],
                ],
            ]);
    }
    public function test_categories_sorted_by_sort_order_when_question_count_is_tied(): void
    {
        $category1 = QuestionCategory::factory()->create(['sort_order' => 2]);
        $category2 = QuestionCategory::factory()->create(['sort_order' => 1]);
        $question1 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question1->categories()->attach($category1->id);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question1->id,
        ]);
        $question2 = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $question2->categories()->attach($category2->id);
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question2->id,
        ]);
        $response = $this->getReviewSet();
        $data = $response->json('categories');
        $this->assertEquals($category2->id, $data[0]['id']);
        $this->assertEquals($category1->id, $data[1]['id']);
    }
    public function test_does_not_include_other_users_review_questions(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);
        $question = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        ReviewQuestionState::factory()->create([
            'user_id' => $otherUser->id,
            'question_id' => $question->id,
        ]);
        $response = $this->getReviewSet();
        $response->assertStatus(200)
            ->assertJson([
                'question_count' => 0,
                'priority' => 'non',
            ]);
    }
}
