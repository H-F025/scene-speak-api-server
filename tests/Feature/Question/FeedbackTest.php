<?php

namespace Tests\Feature\Question;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\QuestionChoice;
use App\Models\ReviewSet;
use App\Models\ReviewSetQuestion;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
class FeedbackTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private ThemeLevel $themeLevel;
    private Question $question;
    private QuestionChoice $correctChoice;
    private QuestionChoice $incorrectChoice;
    private LearningSession $learningSession;
    private QuestionAttempt $questionAttempt;
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
        $this->question = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'sort_order' => 1,
        ]);
        $this->correctChoice = QuestionChoice::factory()->create([
            'question_id' => $this->question->id,
            'is_correct' => true,
        ]);
        $this->incorrectChoice = QuestionChoice::factory()->create([
            'question_id' => $this->question->id,
            'is_correct' => false,
        ]);
        $this->learningSession = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_id' => $this->themeLevel->id,
            'learning_target_type' => 'normal',
            'status' => 'in_progress',
        ]);
        $this->questionAttempt = QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $this->learningSession->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->incorrectChoice->id,
            'is_correct' => false,
        ]);
    }
    private function getfeedback(int $questionAttemptId): TestResponse
    {
        return $this->actingAs($this->user)
            ->getJson("/api/v1/question-attempts/{$questionAttemptId}");
    }
    public function test_returns_feedback_on_incorrect_answer(): void
    {
        $response = $this->getfeedback($this->questionAttempt->id);
        $response->assertStatus(200)
            ->assertJson([
                'question_attempt_id' => $this->questionAttempt->id,
                'learning_session_id' => $this->learningSession->id,
                'result' => [
                    'is_correct' => false,
                    'selected_choice' => [
                        'id' => $this->incorrectChoice->id,
                        'content' => $this->incorrectChoice->content,
                    ],
                    'correct_choice' => [
                        'id' => $this->correctChoice->id,
                        'content' => $this->correctChoice->content,
                    ],
                    'explanation' => $this->question->incorrect_explanation,
                ],
            ]);
    }
    public function test_returns_feedback_on_correct_answer(): void
    {
        $this->questionAttempt->update([
            'question_choice_id' => $this->correctChoice->id,
            'is_correct' => true,
        ]);
        $response = $this->getfeedback($this->questionAttempt->id);
        $response->assertStatus(200)
            ->assertJson([
                'result' => [
                    'is_correct' => true,
                    'explanation' => $this->question->correct_explanation,
                ],
            ]);
    }
    public function test_returns_next_question_id_when_next_question_exists(): void
    {
        $nextQuestion = Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
            'sort_order' => 2,
        ]);
        $response = $this->getfeedback($this->questionAttempt->id);
        $response->assertStatus(200)
            ->assertJson(['next_question_id' => $nextQuestion->id]);
    }
    public function test_returns_null_for_next_question_id_when_no_next_question(): void
    {
        $response = $this->getfeedback($this->questionAttempt->id);
        $response->assertStatus(200)
            ->assertJson(['next_question_id' => null]);
    }
    public function test_updates_last_activity_at_of_learning_session(): void
    {
        $before = now()->subMinute()->toDateTimeString();
        $this->getfeedback($this->questionAttempt->id);
        $updated = LearningSession::find($this->learningSession->id);
        $this->assertGreaterThan($before, $updated->last_activity_at);
    }
    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson("/api/v1/question-attempts/{$this->questionAttempt->id}");
        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }
    public function test_returns_404_when_attempt_not_found(): void
    {
        $response = $this->getfeedback(999);
        $response->assertStatus(404)
            ->assertJson(['message' => '回答結果が見つかりません。']);
    }
    public function test_returns_404_when_attempt_belongs_to_other_user(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);
        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/question-attempts/{$this->questionAttempt->id}");
        $response->assertStatus(404)
            ->assertJson(['message' => '回答結果が見つかりません。']);
    }

    public function test_returns_next_review_set_question_id_when_next_review_question_exists(): void
    {
        $reviewSet = ReviewSet::factory()->create(['user_id' => $this->user->id]);

        $reviewAttempt = QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $this->learningSession->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->incorrectChoice->id,
            'attempt_type' => 'review',
            'is_correct' => false,
        ]);

        $currentReviewSetQuestion = ReviewSetQuestion::factory()->create([
            'review_set_id' => $reviewSet->id,
            'question_id' => $this->question->id,
            'question_attempt_id' => $reviewAttempt->id,
            'order_no' => 1,
        ]);

        $nextQuestion = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
        $nextReviewSetQuestion = ReviewSetQuestion::factory()->create([
            'review_set_id' => $reviewSet->id,
            'question_id' => $nextQuestion->id,
            'order_no' => 2,
        ]);

         $response = $this->getfeedback($reviewAttempt->id);

        $response->assertStatus(200)
            ->assertJson(['next_question_id' => $nextReviewSetQuestion->id]);
    }

    public function test_returns_null_for_next_question_id_when_no_next_review_question(): void
    {
        $reviewSet = ReviewSet::factory()->create(['user_id' => $this->user->id]);

        $reviewAttempt = QuestionAttempt::factory()->create([
            'user_id' => $this->user->id,
            'learning_session_id' => $this->learningSession->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->incorrectChoice->id,
            'attempt_type' => 'review',
            'is_correct' => false,
        ]);

        ReviewSetQuestion::factory()->create([
            'review_set_id' => $reviewSet->id,
            'question_id' => $this->question->id,
            'question_attempt_id' => $reviewAttempt->id,
            'order_no' => 1,
        ]);

        $response = $this->getfeedback($reviewAttempt->id);

        $response->assertStatus(200)
            ->assertJson(['next_question_id' => null]);
    }
}
