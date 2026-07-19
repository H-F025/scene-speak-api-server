<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\EnglishLevel;
use App\Models\LearningSession;
use App\Models\QuestionAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'message' => 'メールアドレスがすでに登録されています。',
            ], 409);
        }

        $englishLevel = EnglishLevel::find($request->english_level);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'english_level_id' => $englishLevel->id,
        ]);

        Auth::login($user);

        return response()->json([
            'message' => 'ユーザー登録が完了しました。',
            'user' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'english_level' => $englishLevel->code,
                'english_level_label' => $englishLevel->name,
            ],
        ], 201);
    }

public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return response()->json([
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        $englishLevel = $user->englishLevel;

        return response()->json([
            'message' => 'ログインしました。',
            'user' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'english_level' => $englishLevel->code,
                'english_level_label' => $englishLevel->name,
            ],
        ]);
    }
    public function me(Request $request): JsonResponse
    {
    $user = Auth::user();
    $englishLevel = $user->englishLevel;

    $totalStudySeconds = LearningSession::where('user_id', $user->id)
        ->whereIn('status', ['completed', 'interrupted', 'abandoned'])
        ->where('duration_seconds', '>', 0)
        ->sum('duration_seconds');

    $conversationCount = QuestionAttempt::where('user_id', $user->id)
        ->where('attempt_type', 'theme')
        ->count();

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

    $today = CarbonImmutable::today();
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

    if ($totalStudySeconds === 0) {
        $totalStudyTimeLabel = '0分';
    } elseif ($totalStudySeconds < 60) {
        $totalStudyTimeLabel = '1分未満';
    } else {
        $totalStudyMinutes = floor($totalStudySeconds / 60);
        $totalStudyTimeLabel = $totalStudyMinutes.'分';
    }

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'english_level' => $englishLevel->code,
            'english_level_label' => $englishLevel->name,
            ],
        'study_summary' => [
            'consecutive_days' => $consecutiveDays,
            'conversation_count' => $conversationCount,
            'total_study_seconds' => $totalStudySeconds,
            'total_study_time_label' => $totalStudyTimeLabel,
            ],
        ]);
    }

        public function logout(Request $request): JsonResponse
    {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

        return response()->json([
        'message' => 'ログアウトしました。',
        ]);
    }
}
