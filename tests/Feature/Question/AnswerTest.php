<?php

namespace Tests\Feature\Question;

use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionProgress;
use App\Models\ReviewQuestionState;
use App\Models\Theme;
use App\Models\ThemeLearningProgress;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
class AnswerTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private ThemeLevel $themeLevel;
    private Question $question;
    private QuestionChoice $correctChoice;
    private QuestionChoice $incorrectChoice;
    private LearningSession $learningSession;
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
    }
    private function postAnswer(int $questionChoiceId): TestResponse
    {
        return $this->actingAs($this->user)
            ->postJson(
                "/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}/answer",
                ['question_choice_id' => $questionChoiceId]
            );
    }
    public function test_returns_question_attempt_id_on_correct_answer(): void
    {
        $response = $this->postAnswer($this->correctChoice->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['question_attempt_id']);
        $this->assertDatabaseHas('question_attempts', [
            'user_id' => $this->user->id,
            'learning_session_id' => $this->learningSession->id,
            'question_id' => $this->question->id,
            'question_choice_id' => $this->correctChoice->id,
            'attempt_type' => 'theme',
            'is_correct' => true,
        ]);
    }
    public function test_returns_question_attempt_id_on_incorrect_answer(): void
    {
        $response = $this->postAnswer($this->incorrectChoice->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['question_attempt_id']);
        $this->assertDatabaseHas('question_attempts', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'is_correct' => false,
        ]);
    }
    public function test_creates_question_progress_on_first_answer(): void
    {
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseCount('question_progresses', 1);
        $this->assertDatabaseHas('question_progresses', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'is_completed' => true,
            'is_correct' => true,
        ]);
    }
    public function test_updates_question_progress_on_re_answer(): void
    {
        $themeLearningProgress = ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
            'completed_problem_count' => 1,
        ]);
        QuestionProgress::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'theme_learning_progress_id' => $themeLearningProgress->id,
            'is_correct' => false,
        ]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseCount('question_progresses', 1);
        $this->assertDatabaseHas('question_progresses', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'is_correct' => true,
        ]);
    }
    public function test_creates_theme_learning_progress_if_not_exists(): void
    {
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('theme_learning_progresses', [
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
        ]);
    }
    public function test_increments_completed_problem_count_on_first_answer(): void
    {
        $themeLearningProgress = ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
            'completed_problem_count' => 2,
        ]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('theme_learning_progresses', [
            'id' => $themeLearningProgress->id,
            'completed_problem_count' => 3,
        ]);
    }
    public function test_does_not_increment_completed_problem_count_on_re_answer(): void
    {
        $themeLearningProgress = ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
            'completed_problem_count' => 2,
        ]);
        QuestionProgress::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'theme_learning_progress_id' => $themeLearningProgress->id,
        ]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('theme_learning_progresses', [
            'id' => $themeLearningProgress->id,
            'completed_problem_count' => 2,
        ]);
    }
    public function test_creates_review_question_state_on_incorrect_answer(): void
    {
        $this->postAnswer($this->incorrectChoice->id);
        $this->assertDatabaseHas('review_question_states', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'status' => 'needs_review',
            'incorrect_count' => 1,
        ]);
    }
    public function test_increments_incorrect_count_when_review_state_already_exists(): void
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
    public function test_resolves_review_question_state_on_correct_answer(): void
    {
        ReviewQuestionState::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'status' => 'needs_review',
            'incorrect_count' => 1,
        ]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('review_question_states', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'status' => 'resolved',
        ]);
    }
    public function test_does_not_create_review_state_on_correct_answer(): void
    {
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseCount('review_question_states', 0);
    }
    public function test_updates_last_activity_at_of_learning_session(): void
    {
        $before = now()->subMinute()->toDateTimeString();
        $this->postAnswer($this->correctChoice->id);
        $updated = LearningSession::find($this->learningSession->id);
        $this->assertGreaterThan($before, $updated->last_activity_at);
    }
    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson(
            "/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}/answer",
            ['question_choice_id' => $this->correctChoice->id]
        );
        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }
    public function test_returns_409_when_session_is_completed(): void
    {
        $this->learningSession->update(['status' => 'completed']);
        $response = $this->postAnswer($this->correctChoice->id);
        $response->assertStatus(409)
            ->assertJson(['message' => 'この学習セッションはすでに終了しています。']);
    }
    public function test_returns_404_when_session_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(
                "/api/v1/learning-sessions/999/questions/{$this->question->id}/answer",
                ['question_choice_id' => $this->correctChoice->id]
            );
        $response->assertStatus(404)
            ->assertJson(['message' => '学習セッションが見つかりません。']);
    }
    public function test_returns_422_when_choice_does_not_belong_to_question(): void
    {
        $otherQuestion = Question::factory()->create(['theme_level_id' => $this->themeLevel->id]);
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
            ->postJson(
                "/api/v1/learning-sessions/{$this->learningSession->id}/questions/{$this->question->id}/answer",
                []
            );
        $response->assertStatus(422);
    }
    public function test_question_attempt_id_matches_db_record(): void
    {
        $response = $this->postAnswer($this->correctChoice->id);
        $response->assertStatus(200);
        $attemptId = $response->json('question_attempt_id');
        $this->assertDatabaseHas('question_attempts', ['id' => $attemptId]);
    }
    public function test_multiple_answers_to_same_question_create_multiple_attempts(): void
    {
        $this->postAnswer($this->incorrectChoice->id);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseCount('question_attempts', 2);
        $this->assertDatabaseCount('question_progresses', 1);
    }
    public function test_sets_theme_learning_progress_to_completed_when_all_questions_answered(): void
    {
        // setUp で問題が1問だけ作られているので、その1問に答えると全問完了になる
        $themeLearningProgress = ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
            'status' => 'in_progress',
            'completed_problem_count' => 0,
        ]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('theme_learning_progresses', [
            'id' => $themeLearningProgress->id,
            'status' => 'completed',
            'completed_problem_count' => 1,
        ]);
    }
    public function test_does_not_complete_theme_learning_progress_when_questions_remain(): void
    {
        Question::factory()->create([
            'theme_level_id' => $this->themeLevel->id,
        ]);
        $themeLearningProgress = ThemeLearningProgress::factory()->create([
            'user_id' => $this->user->id,
            'theme_level_id' => $this->themeLevel->id,
            'status' => 'in_progress',
            'completed_problem_count' => 0,
        ]);
        $this->postAnswer($this->correctChoice->id);
        $this->assertDatabaseHas('theme_learning_progresses', [
            'id' => $themeLearningProgress->id,
            'status' => 'in_progress',
        ]);
    }
}