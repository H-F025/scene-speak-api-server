<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexHistoryRequest;
use App\Models\LearningSession;
use App\Models\QuestionAttempt;
use App\Models\ReviewSet;
use App\Models\ThemeLevel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
class HistoryController extends Controller
{
    public function index(IndexHistoryRequest $request): JsonResponse
    {
        $user = Auth::user();
        // 学習済みとして扱うセッション状態を用意する
        // in_progress はまだ学習中なので、履歴や集計には含めない
        $finishedStatuses = [
            'completed',
            'interrupted',
            'abandoned',
        ];
        // 連続学習日数を計算する
        // 学習履歴画面の上部に表示するため
        $streakDays = $this->calculateStreakDays($user->id, $finishedStatuses);
        // 通常学習で回答した回数を取得する
        // DB設計どおりなら attempt_type = 1 が通常学習
        //
        // もし実際のDBで attempt_type を文字列にしている場合は、
        // 1 ではなく 'theme' や 'normal' に変更する
        $conversationCount = QuestionAttempt::where('user_id', $user->id)
            ->where('attempt_type', 'theme')
            ->count();
        // 総学習時間を秒数で取得する
        // duration_seconds が 0 のセッションは、総学習時間には足さない
        //
        // 10秒未満でも回答済みの場合に総学習時間へ入れたい場合は、
        // 学習セッション終了API側で duration_seconds に実際の秒数を保存しておく必要がある
        $totalStudySeconds = LearningSession::where('user_id', $user->id)
            ->whereIn('status', $finishedStatuses)
            ->where('duration_seconds', '>', 0)
            ->sum('duration_seconds');
        $totalStudySeconds = (int) $totalStudySeconds;
        // 通常学習の履歴カードを作る
        $normalCards = $this->buildNormalCards($user->id, $finishedStatuses);
        // 復習の履歴カードを作る
        $reviewCards = $this->buildReviewCards($user->id, $finishedStatuses);
        // 通常学習と復習の履歴カードを1つにまとめる
        $allCards = $normalCards->merge($reviewCards);
        // 履歴カードを新しい順に並び替える
        // sort_at が大きいものほど新しい履歴なので、上に表示する
        $allCards = $allCards->sort(function ($a, $b) {
            if ($a['sort_at'] > $b['sort_at']) {
                return -1;
            }
            if ($a['sort_at'] < $b['sort_at']) {
                return 1;
            }
            return 0;
        })->values();
        // 1ページあたりの表示件数
        $perPage = 20;
        // リクエストの page を取得する
        // page が指定されていない場合は 1ページ目にする
        $page = (int) $request->input('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        // Collection を手動でページネーションする
        // 通常学習と復習を合体してから並び替えているため、DBの paginate は使いにくい
        $paginator = new LengthAwarePaginator(
            $allCards->forPage($page, $perPage)->values(),
            $allCards->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        // 月ごとに履歴をまとめるための配列
        $groups = [];
        foreach ($paginator->items() as $card) {
            // 例：2026年5月
            $groupKey = $card['year_month_label'];
            // まだこの月のグループがなければ作る
            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'year_month' => $groupKey,
                    'histories' => [],
                ];
            }
            // フロントに返す履歴データを追加する
            // sort_at など、画面に不要な内部用データは返さない
            $groups[$groupKey]['histories'][] = [
                'history_type' => $card['history_type'],
                'type_label' => $card['type_label'],
                'title' => $card['title'],
                'learned_on' => $card['learned_on'],
                'study_time' => $card['study_time'],
                'summary' => $card['summary'],
            ];
        }
        return response()->json([
            'study_summary' => [
                'streak_days' => $streakDays,
                'conversation_count' => $conversationCount,
                'total_study_time' => $this->formatStudyTime($totalStudySeconds),
            ],
            'history_groups' => array_values($groups),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
    private function calculateStreakDays(int $userId, array $finishedStatuses): int
    {
        // 連続学習日数の対象になる学習セッションを取得する
        //
        // duration_seconds > 0 の場合：
        // 学習時間が発生しているので、学習した日として扱う
        //
        // questionAttempts がある場合：
        // 10秒未満などで duration_seconds が 0 の古いデータでも、
        // 回答済みなら学習した日として扱いたいため
        $sessions = LearningSession::where('user_id', $userId)
            ->whereIn('status', $finishedStatuses)
            ->whereNotNull('ended_at')
            ->where(function ($query) {
                $query->where('duration_seconds', '>', 0)
                    ->orWhereHas('questionAttempts');
            })
            ->orderBy('ended_at', 'desc')
            ->get(['id', 'ended_at']);
        // 学習した日付を入れる配列
        // 同じ日に複数回学習しても、日付は1回だけ入れる
        $studiedDates = [];
        foreach ($sessions as $session) {
            $date = CarbonImmutable::parse($session->ended_at)->toDateString();
            if (! in_array($date, $studiedDates, true)) {
                $studiedDates[] = $date;
            }
        }
        // 今日と昨日を用意する
        // 今日まだ学習していなくても、昨日まで続いていれば連続日数として表示するため
        $today = CarbonImmutable::today();
        $yesterday = $today->subDay();
        // どの日付から連続日数を数え始めるかを入れる変数
        $checkDate = null;
        if (in_array($today->toDateString(), $studiedDates, true)) {
            $checkDate = $today;
        }
        if ($checkDate === null && in_array($yesterday->toDateString(), $studiedDates, true)) {
            $checkDate = $yesterday;
        }
        // 今日も昨日も学習していない場合は、連続記録は0日
        if ($checkDate === null) {
            return 0;
        }
        $streakDays = 0;
        foreach ($studiedDates as $date) {
            // 確認したい日付と、実際に学習した日付が一致するか確認する
            if ($date === $checkDate->toDateString()) {
                // 一致したら連続日数を1日増やす
                $streakDays++;
                // 次は1日前の日付を確認する
                // CarbonImmutable は自分自身を変更しないので、代入が必要
                $checkDate = $checkDate->subDay();
            } else {
                // 途中で学習していない日があれば、そこで連続記録は終了
                break;
            }
        }
        return $streakDays;
    }
    private function buildNormalCards(int $userId, array $finishedStatuses): Collection
    {
        // 通常学習の終了済みセッションを取得する
        // questionAttempts も一緒に取得して、回答数や正解数を集計する
        $sessions = LearningSession::where('user_id', $userId)
            ->where('learning_target_type', 'normal')
            ->whereIn('status', $finishedStatuses)
            ->whereNotNull('ended_at')
            ->with('questionAttempts')
            ->get();
        // 同じ日・同じテーマレベルの履歴をまとめるための配列
        $groups = [];
        foreach ($sessions as $session) {
            $attempts = $session->questionAttempts;
            $attemptCount = $attempts->count();
            // 履歴に表示するかどうかを判定する
            // duration_seconds が 0 でも、回答済みなら履歴に表示する
            // これにより、10秒未満でも回答済みなら履歴に出せる
            if (! $this->shouldShowHistory((int) $session->duration_seconds, $attemptCount)) {
                continue;
            }
            // 学習時間はDBに保存されている duration_seconds をそのまま使う
            // 10秒未満でも回答済みなら、終了API側で実際の秒数が保存されている想定
            $durationSeconds = (int) $session->duration_seconds;
            if ($durationSeconds < 0) {
                $durationSeconds = 0;
            }
            $endedAt = CarbonImmutable::parse($session->ended_at);
            $date = $endedAt->toDateString();
            // learning_target_id がない場合はテーマが特定できないため、履歴カードを作れない
            if ($session->learning_target_id === null) {
                continue;
            }
            // 同じ日・同じテーマレベルでまとめる
            // 例：2026-05-22_3
            $key = $date.'_'.$session->learning_target_id;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'date' => $date,
                    'learning_target_id' => $session->learning_target_id,
                    'total_duration' => 0,
                    'attempt_count' => 0,
                    'correct_count' => 0,
                    'max_ended_at' => $session->ended_at,
                ];
            }
            // 同じ日・同じテーマレベルの学習時間を合計する
            $groups[$key]['total_duration'] += $durationSeconds;
            // 回答数を合計する
            $groups[$key]['attempt_count'] += $attemptCount;
            // 正解数を合計する
            foreach ($attempts as $attempt) {
                if ($attempt->is_correct) {
                    $groups[$key]['correct_count']++;
                }
            }
            // 同じグループ内で一番新しい終了時刻を保存する
            // 履歴カードの並び替えに使うため
            if ($session->ended_at > $groups[$key]['max_ended_at']) {
                $groups[$key]['max_ended_at'] = $session->ended_at;
            }
        }
        if (empty($groups)) {
            return collect();
        }
        // 履歴カードのタイトルにテーマ名を表示するため、
        // theme_levels と themes を取得する
        $themeLevelIds = [];
        foreach ($groups as $group) {
            if (! in_array($group['learning_target_id'], $themeLevelIds, true)) {
                $themeLevelIds[] = $group['learning_target_id'];
            }
        }
        $themeLevelModels = ThemeLevel::whereIn('id', $themeLevelIds)
            ->with('theme')
            ->get();
        // theme_level_id で探しやすくするための配列を作る
        $themeLevels = [];
        foreach ($themeLevelModels as $themeLevel) {
            $themeLevels[$themeLevel->id] = $themeLevel;
        }
        $cards = [];
        foreach ($groups as $group) {
            $themeLevelId = $group['learning_target_id'];
            // テーマレベルが見つからない場合は、履歴カードを作れないためスキップする
            if (! isset($themeLevels[$themeLevelId])) {
                continue;
            }
            $themeLevel = $themeLevels[$themeLevelId];
            // テーマが見つからない場合も、タイトルを作れないためスキップする
            if (! $themeLevel->theme) {
                continue;
            }
            $endedAt = CarbonImmutable::parse($group['max_ended_at']);
            $cards[] = [
                'history_type' => 'normal',
                'type_label' => '会話練習',
                'title' => $themeLevel->theme->name,
                'learned_on' => $endedAt->format('n月j日'),
                'study_time' => $this->formatStudyTime((int) $group['total_duration']),
                'summary' => $group['attempt_count'].'問学習・'.$group['correct_count'].'問正解',
                'sort_at' => $endedAt->format('Y-m-d H:i:s'),
                'year_month_label' => $endedAt->format('Y年n月'),
            ];
        }
        return collect($cards);
    }
    private function buildReviewCards(int $userId, array $finishedStatuses): Collection
    {
        // 復習の終了済みセッションを取得する
        // ended_at がないと履歴の日付を作れないため除外する
        $sessions = LearningSession::where('user_id', $userId)
            ->where('learning_target_type', 'review')
            ->whereIn('status', $finishedStatuses)
            ->whereNotNull('ended_at')
            ->get();
        if ($sessions->isEmpty()) {
            return collect();
        }
        // learning_target_id には review_sets.id が入っている想定
        // 復習セットの正解数・不正解数・スキップ数を表示するために取得する
        $reviewSetIds = [];
        foreach ($sessions as $session) {
            if ($session->learning_target_id === null) {
                continue;
            }
            if (! in_array($session->learning_target_id, $reviewSetIds, true)) {
                $reviewSetIds[] = $session->learning_target_id;
            }
        }
        if (empty($reviewSetIds)) {
            return collect();
        }
        $reviewSetModels = ReviewSet::whereIn('id', $reviewSetIds)
            ->get();
        // review_set_id で探しやすくするための配列を作る
        $reviewSets = [];
        foreach ($reviewSetModels as $reviewSet) {
            $reviewSets[$reviewSet->id] = $reviewSet;
        }
        $cards = [];
        foreach ($sessions as $session) {
            $reviewSetId = $session->learning_target_id;
            // 復習セットが見つからない場合は、summary を作れないためスキップする
            if (! isset($reviewSets[$reviewSetId])) {
                continue;
            }
            $reviewSet = $reviewSets[$reviewSetId];
            // 復習で回答・不正解・スキップした合計数を出す
            // これが1以上なら、10秒未満でも履歴に表示する
            $answeredCount = $reviewSet->correct_count
                + $reviewSet->incorrect_count
                + $reviewSet->skipped_count;
            // duration_seconds が 0 でも、回答済みなら履歴に表示する
            if (! $this->shouldShowHistory((int) $session->duration_seconds, $answeredCount)) {
                continue;
            }
            // 学習時間はDBに保存されている duration_seconds をそのまま使う
            $durationSeconds = (int) $session->duration_seconds;
            if ($durationSeconds < 0) {
                $durationSeconds = 0;
            }
            $endedAt = CarbonImmutable::parse($session->ended_at);
            $summary = $answeredCount.'問復習・'.$reviewSet->correct_count.'問正解';
            if ($reviewSet->skipped_count > 0) {
                $summary .= '・'.$reviewSet->skipped_count.'問スキップ';
            }
            $cards[] = [
                'history_type' => 'review',
                'type_label' => '復習',
                'title' => '苦手問題集の復習',
                'learned_on' => $endedAt->format('n月j日'),
                'study_time' => $this->formatStudyTime($durationSeconds),
                'summary' => $summary,
                'sort_at' => $endedAt->format('Y-m-d H:i:s'),
                'year_month_label' => $endedAt->format('Y年n月'),
            ];
        }
        return collect($cards);
    }
    private function shouldShowHistory(int $durationSeconds, int $answeredCount): bool
    {
        // 10秒未満かつ未回答の場合は表示しない
        return ! ($durationSeconds < 10 && $answeredCount === 0);
    }
    private function formatStudyTime(int $seconds): string
    {
        if ($seconds < 60) {
            return '1分未満';
        }
        return ((int) floor($seconds / 60)).'分';
    }
}