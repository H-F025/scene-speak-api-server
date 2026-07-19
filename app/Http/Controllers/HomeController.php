<?php

namespace App\Http\Controllers;

use App\Models\LearningSession;
use App\Models\ReviewQuestionState;
use App\Models\ThemeLearningProgress;
use App\Models\ThemeLevel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function show(): JsonResponse
    {
        $user = Auth::user();
        $englishLevel = $user->englishLevel;

        // 当日の学習時間を算出する
        // 日跨ぎセッションは、当日に含まれる秒数のみを集計する
        $today = CarbonImmutable::today();
        $tomorrow = $today->addDay();

        $todaySessions = LearningSession::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'interrupted', 'abandoned'])
            ->where('duration_seconds', '>', 0)
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('started_at', '<', $tomorrow)
            ->where('ended_at', '>', $today)
            ->get();

        $todayStudySeconds = 0;

        foreach ($todaySessions as $session) {
            $sessionStart = CarbonImmutable::parse($session->started_at);
            $sessionEnd = CarbonImmutable::parse($session->ended_at);

            $durationSeconds = (int) $session->duration_seconds;

            if ($durationSeconds <= 0) {
                continue;
            }

            $effectiveSessionEnd = $sessionStart->addSeconds($durationSeconds);

            if ($effectiveSessionEnd > $sessionEnd) {
                $effectiveSessionEnd = $sessionEnd;
            }

            $overlapStartTs = max($sessionStart->timestamp, $today->timestamp);
            $overlapEndTs = min($effectiveSessionEnd->timestamp, $tomorrow->timestamp);

            if ($overlapEndTs > $overlapStartTs) {
                $todayStudySeconds += $overlapEndTs - $overlapStartTs;
            }
        }

        $todayStudyMinutes = (string) floor($todayStudySeconds / 60);

        // 連続学習日数を算出する
        $studiedDates = [];

        $learningSessions = LearningSession::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'interrupted', 'abandoned'])
            ->where('duration_seconds', '>', 0)
            ->orderBy('started_at', 'desc')
            ->get();

        foreach ($learningSessions as $learningSession) {
            $studyDate = CarbonImmutable::parse($learningSession->started_at)->toDateString();
            if (! in_array($studyDate, $studiedDates, true)) {
                $studiedDates[] = $studyDate;
            }
        }

        $consecutiveDays = 0;
        $yesterday = CarbonImmutable::yesterday();
        $checkDate = null;

        if (in_array($today->toDateString(), $studiedDates, true)) {
            $checkDate = $today;
        }

        if ($checkDate === null && in_array($yesterday->toDateString(), $studiedDates, true)) {
            $checkDate = $yesterday;
        }

        if ($checkDate !== null) {
            foreach ($studiedDates as $studyDate) {
                if ($studyDate === $checkDate->toDateString()) {
                    $consecutiveDays++;
                    $checkDate = $checkDate->subDay();
                } else {
                    break;
                }
            }
        }

        // おすすめテーマを算出する
        $themeLevels = ThemeLevel::where('theme_levels.english_level_id', $user->english_level_id)
            ->join('themes', 'theme_levels.theme_id', '=', 'themes.id')
            ->orderBy('themes.sort_order')
            ->orderBy('theme_levels.sort_order')
            ->orderBy('theme_levels.id')
            ->select('theme_levels.*')
            ->with('theme')
            ->get();

        $progresses = ThemeLearningProgress::where('user_id', $user->id)
            ->whereIn('theme_level_id', $themeLevels->pluck('id'))
            ->get()
            ->keyBy('theme_level_id');

        $inProgressThemeLevel = null;
        $notStartedThemeLevel = null;
        $completedThemeLevel = null;
        $completedOldestAt = null;

        foreach ($themeLevels as $themeLevel) {
            $progress = $progresses->get($themeLevel->id);

            if ($progress === null) {
                if ($notStartedThemeLevel === null) {
                    $notStartedThemeLevel = $themeLevel;
                }
            } elseif ($progress->status === 'in_progress') {
                if ($inProgressThemeLevel === null) {
                    $inProgressThemeLevel = $themeLevel;
                }
            } elseif ($progress->status === 'completed') {
                if ($completedThemeLevel === null || $progress->last_studied_at < $completedOldestAt) {
                    $completedThemeLevel = $themeLevel;
                    $completedOldestAt = $progress->last_studied_at;
                }
            }
        }

        $recommendedThemeLevel = $inProgressThemeLevel ?? $notStartedThemeLevel ?? $completedThemeLevel;

        $recommendedTheme = null;
        if ($recommendedThemeLevel !== null) {
            $theme = $recommendedThemeLevel->theme;
            $estimatedMinutes = $recommendedThemeLevel->estimated_minutes;
            $estimatedTimeLabel = $estimatedMinutes !== null ? "約{$estimatedMinutes}分" : '制限なし';

            $recommendedTheme = [
                'theme_level_id' => $recommendedThemeLevel->id,
                'theme_id' => $theme->id,
                'name' => $theme->name,
                'description' => $theme->description,
                'english_level' => $englishLevel->code,
                'english_level_label' => $englishLevel->name,
                'estimated_minutes' => $estimatedMinutes,
                'estimated_time_label' => $estimatedTimeLabel,
            ];
        }

        // 復習対象の問題があるか確認する
        $reviewFrom = now()->subDays(6)->startOfDay();
        $hasReviewSet = ReviewQuestionState::where('user_id', $user->id)
            ->where('status', 'needs_review')
            ->where('updated_at', '>=', $reviewFrom)
            ->exists();

        return response()->json([
            'user_name' => $user->name,
            'stats' => [
                'streak_days' => $consecutiveDays,
                'today_study_time' => $todayStudyMinutes,
            ],
            'recommended_theme' => $recommendedTheme,
            'has_review_set' => $hasReviewSet,
        ]);
    }
}