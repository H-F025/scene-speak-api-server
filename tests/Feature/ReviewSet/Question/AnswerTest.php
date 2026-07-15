<?php

namespace Tests\Feature\ReviewSet\Question;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\ReviewQuestionState;
use App\Models\ReviewSet;
use App\Models\ReviewSetQuestion;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
class AnswerTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private ReviewSet $reviewSet;
    private ReviewSetQuestion $reviewSetQuestion;
    private Question $question;
    private QuestionChoice $correctChoice;
    private QuestionChoice $incorrectChoice;
    private LearningSession $learningSession;
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
        $this->correctChoice = QuestionChoice::factory()->create([
            'question_id' => $this->question->id,
            'is_correct' => true,
        ]);
        $this->incorrectChoice = QuestionChoice::factory()->create([
            'question_id' => $this->question->id,
            'is_correct' => false,
        ]);
        $this->reviewSet = ReviewSet::factory()->create([
            'user_id' => $this->user->id,
            'target_question_count' => 1,
            'correct_count' => 0,
            'incorrect_count' => 0,
        ]);
        $this->reviewSetQuestion = ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $this->question->id,
            'order_no' => 1,
            'result' => 'not_answered',
        ]);
        $this->learningSession = LearningSession::factory()->create([
            'user_id' => $this->user->id,
            'learning_target_type' => 'review',
            'learning_target_id' => $this->reviewSet->id,
            'status' => 'in_progress',
        ]);
    }
    private function postUrl(?int $reviewSetId = null, ?int $reviewSetQuestionId = null): string
    {
        $reviewSetId ??= $this->reviewSet->id;
        $reviewSetQuestionId ??= $this->reviewSetQuestion->id;
        return "/api/v1/review-sets/{$reviewSetId}/questions/{$reviewSetQuestionId}/answer";
    }
    private function postAnswer(int $questionChoiceId, ?int $reviewSetId = null, ?int $reviewSetQuestionId = null): TestResponse
    {
        return $this->actingAs($this->user)
            ->postJson(
                $this->postUrl($reviewSetId, $reviewSetQuestionId),
                ['question_choice_id' => $questionChoiceId]
            );
    }
    public function test_returns_201_with_question_attempt_id_on_correct_answer(): void
    {
        $response = $this->postAnswer($this->correctChoice->id);
        $response->assertStatus(201)
            ->assertJsonStructure(['question_attempt_id']);
    }
    public function test_returns_201_with_question_attempt_id_on_incorrect_answer(): void
    {
        $response = $this->postAnswer($this->incorrectChoice->id);
        $response->assertStatus(201)
            ->assertJsonStructure(['question_attempt_id']);
    }
    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson($this->postUrl(), ['question_choice_id' => $this->correctChoice->id]);
        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }
    public function test_returns_404_when_review_set_not_found(): void
    {
        $response = $this->postAnswer($this->correctChoice->id, reviewSetId: 9999);
        $response->assertStatus(404)
            ->assertJson(['message' => '復習問題が見つかりません。']);
    }
    public function test_returns_404_when_review_set_belongs_to_other_user(): void
    {
        $otherUser = User::factory()->create(['english_level_id' => $this->user->english_level_id]);
        $otherReviewSet = ReviewSet::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->postAnswer($this->correctChoice->id, reviewSetId: $otherReviewSet->id);
        $response->assertStatus(404)
            ->assertJson(['message' => '復習問題が見つかりません。']);
    }
    public function test_returns_404_when_review_set_question_not_found(): void
    {
        $response = $this->postAnswer($this->correctChoice->id, reviewSetQuestionId: 9999);
        $response->assertStatus(404)
            ->assertJson(['message' => '復習問題が見つかりません。']);
    }
    public function test_returns_409_when_already_answered(): void
    {
        $this->reviewSetQuestion->update(['result' => 'correct']);
        $response = $this->postAnswer($this->correctChoice->id);
        $response->assertStatus(409)
            ->assertJson(['message' => 'この復習問題はすでに解答済みです。']);
    }
    public function test_returns_422_when_choice_does_not_belong_to_question(): void
    {
        $otherQuestion = Question::factory()->create(['theme_level_id' => $this->question->theme_level_id]);
        $otherChoice = QuestionChoice::factory()->create([
            'question_id' => $otherQuestion->id,
            'is_correct' => true,
        ]);
        $response = $this->postAnswer($otherChoice->id);
        $response->assertStatus(422)
            ->assertJson([
                'message' => '入力内容に誤りがあります。',
                'errors' => [
                    'question_choice_id' => ['指定された選択肢はこの問題に含まれていません。'],
                ],
            ]);
    }
    public function test_returns_422_when_question_choice_id_is_missing(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson($this->postUrl(), []);
        $response->assertStatus(422);
    }
    public function test_saves_question_attempt_with_review_type(): void
    {
        $response = $this->postAnswer($this->correctChoice->id);
        $response->assertStatus(201);
        $this->assertDatabaseHas('question_attempts', [
            'user_id' => $this->user->id,
            'learning_session_id' => $this->learningSession->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->correctChoice->id,
            'attempt_type' => 'review',
            'is_correct' => true,
        ]);
    }
    public function test_updates_review_set_question_to_correct(): void
    {
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('review_set_questions', [
            'id' => $this->reviewSetQuestion->id,
            'result' => 'correct',
        ]);
    }
    public function test_updates_review_set_question_to_incorrect(): void
    {
        $this->postAnswer($this->incorrectChoice->id);
        $this->assertDatabaseHas('review_set_questions', [
            'id' => $this->reviewSetQuestion->id,
            'result' => 'incorrect',
        ]);
    }
    public function test_sets_question_attempt_id_on_review_set_question(): void
    {
        $response = $this->postAnswer($this->correctChoice->id);
        $attemptId = $response->json('question_attempt_id');
        $this->assertDatabaseHas('review_set_questions', [
            'id' => $this->reviewSetQuestion->id,
            'question_attempt_id' => $attemptId,
        ]);
    }
    public function test_resolves_review_question_state_on_correct_answer(): void
    {
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'status' => 'needs_review',
            'incorrect_count' => 2,
        ]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('review_question_states', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'status' => 'resolved',
        ]);
    }
    public function test_increments_incorrect_count_on_incorrect_answer(): void
    {
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'status' => 'needs_review',
            'incorrect_count' => 3,
        ]);
        $this->postAnswer($this->incorrectChoice->id);
        $this->assertDatabaseHas('review_question_states', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'incorrect_count' => 4,
        ]);
    }
    public function test_increments_correct_count_in_review_set(): void
    {
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('review_sets', [
            'id' => $this->reviewSet->id,
            'correct_count' => 1,
            'incorrect_count' => 0,
        ]);
    }
    public function test_increments_incorrect_count_in_review_set(): void
    {
        $this->postAnswer($this->incorrectChoice->id);
        $this->assertDatabaseHas('review_sets', [
            'id' => $this->reviewSet->id,
            'correct_count' => 0,
            'incorrect_count' => 1,
        ]);
    }
    public function test_updates_last_activity_at_of_learning_session(): void
    {
        $pastTime = now()->subMinutes(10)->toDateTimeString();
        $this->learningSession->update(['last_activity_at' => $pastTime]);
        $this->postAnswer($this->correctChoice->id);
        $this->learningSession->refresh();
        $this->assertNotEquals($pastTime, $this->learningSession->last_activity_at);
    }
    public function test_completes_review_set_when_all_questions_answered(): void
    {
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('review_sets', [
            'id' => $this->reviewSet->id,
            'status' => 'completed',
        ]);
    }
    public function test_does_not_complete_review_set_when_questions_remain(): void
    {
        $englishLevel = $this->user->english_level_id;
        $themeLevel = $this->question->themeLevel;
        $question2 = Question::factory()->create(['theme_level_id' => $themeLevel->id]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question2->id,
            'order_no' => 2,
            'result' => 'not_answered',
        ]);
        $this->reviewSet->update(['target_question_count' => 2]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('review_sets', [
            'id' => $this->reviewSet->id,
            'status' => 'in_progress',
        ]);
    }
    public function test_ends_learning_session_when_review_set_is_completed(): void
    {
        $this->postAnswer($this->correctChoice->id);
        $this->learningSession->refresh();
        $this->assertEquals('completed', $this->learningSession->status);
        $this->assertNotNull($this->learningSession->ended_at);
    }
    public function test_does_not_end_learning_session_when_questions_remain(): void
    {
        $themeLevel = $this->question->themeLevel;
        $question2 = Question::factory()->create(['theme_level_id' => $themeLevel->id]);
        ReviewSetQuestion::factory()->create([
            'review_set_id' => $this->reviewSet->id,
            'question_id' => $question2->id,
            'order_no' => 2,
            'result' => 'not_answered',
        ]);
        $this->reviewSet->update(['target_question_count' => 2]);
        $this->postAnswer($this->correctChoice->id);
        $this->learningSession->refresh();
        $this->assertEquals('in_progress', $this->learningSession->status);
        $this->assertNull($this->learningSession->ended_at);
    }
}