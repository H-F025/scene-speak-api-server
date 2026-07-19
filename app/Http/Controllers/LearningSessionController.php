<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinishLearningSessionRequest;
use App\Http\Requests\StoreLearningSessionRequest;
use App\Models\LearningSession;
use App\Models\QuestionAttempt;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class LearningSessionController extends Controller
{
    public function store(StoreLearningSessionRequest $request): JsonResponse
    {
        $user = Auth::user();

        $learningTargetType = $request->input('learning_type');
        $learningTargetId = $request->input('learning_target_id');

        // 現在時刻を取得する
        // started_at や last_activity_at に保存するために使う
        $now = CarbonImmutable::now();

        // 自動終了の判定に使う時刻を作る
        // last_activity_at がこの時刻より前なら「5分以上通信がない」と判断する
        $fiveMinutesAgo = $now->subMinutes(5);

        // 通常学習の上限時間
        // 30分 = 1800秒
        $normalMaxDurationSeconds = 1800;

        // 復習の上限時間
        // 60分 = 3600秒
        $reviewMaxDurationSeconds = 3600;

        // in_progress かつ last_activity_at から5分以上経過したセッションを interrupted で自動終了する
        $staleSessions = LearningSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('last_activity_at', '<=', $fiveMinutesAgo)
            ->get();

        // 進行中のまま残っている学習セッションを1件ずつ終了する
        foreach ($staleSessions as $staleSession) {
            // 学習タイプによって、学習時間の上限を変える
            if ($staleSession->learning_target_type === 'normal') {
                $maxDurationSeconds = $normalMaxDurationSeconds;
            } else {
                $maxDurationSeconds = $reviewMaxDurationSeconds;
            }

            // 学習を開始した時刻を取得する
            $startedAt = CarbonImmutable::parse($staleSession->started_at);

            // 最後に通信した時刻を取得する
            $lastActivityAt = CarbonImmutable::parse($staleSession->last_activity_at);

            // 開始時刻から最後に通信した時刻まで、何秒たったかを計算する
            $durationSeconds = $startedAt->diffInSeconds($lastActivityAt);

            // 異常に長い学習時間にならないようにする
            // 上限時間を超えていたら、上限時間にする
            if ($durationSeconds > $maxDurationSeconds) {
                $durationSeconds = $maxDurationSeconds;
            }

            // 学習セッションを自動終了する
            $staleSession->update([
                'status' => 'interrupted',
                'ended_at' => $staleSession->last_activity_at,
                'duration_seconds' => $durationSeconds,
            ]);
        }

        // 画面リロードなどで同じ学習対象のセッションがすでに存在する場合は、
        // 新しく作らず、既存のセッションを再利用する
        $activeSession = LearningSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('learning_target_type', $learningTargetType)
            ->where('learning_target_id', $learningTargetId)
            ->where('last_activity_at', '>', $fiveMinutesAgo)
            ->first();

        if ($activeSession) {
            $activeSession->update([
                'last_activity_at' => $now,
            ]);

            return response()->json([
                'learning_session_id' => $activeSession->id,
            ], 201);
        }

        // ここまで来たら、有効な同じ学習セッションは存在しないため新規作成する
        $learningSession = LearningSession::create([
            'user_id' => $user->id,
            'learning_target_type' => $learningTargetType,
            'learning_target_id' => $learningTargetId,
            'status' => 'in_progress',
            'started_at' => $now,
            'last_activity_at' => $now,
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);

        return response()->json([
            'learning_session_id' => $learningSession->id,
        ], 201);
    }

    public function finish(FinishLearningSessionRequest $request, int $learning_session_id): JsonResponse
    {
    $user = Auth::user();

    $learningSession = LearningSession::where('id', $learning_session_id)
        ->where('user_id', $user->id)
        ->first();

    if (! $learningSession) {
        return response()->json(['message' => '学習セッションが見つかりません。'], 404);
    }

    if ($learningSession->status !== 'in_progress') {
        return response()->json(['message' => 'この学習セッションはすでに終了しています。'], 409);
    }

    // 現在時刻を取得する
    // ended_at や学習時間の計算に使うため
    $now = CarbonImmutable::now();

    // 学習開始時刻を取得する
    // started_at から現在時刻までの秒数を計算するため
    $startedAt = CarbonImmutable::parse($learningSession->started_at);

    // 開始時刻から終了時刻までの秒数を計算する
    // 例：10:00開始、10:05終了なら 300秒
    $rawDurationSeconds = $startedAt->diffInSeconds($now);

    // 学習タイプごとの上限秒数を決める
    // 通常学習は30分、復習は60分を上限にするため
    $maxDurationSeconds = 1800;

    if ($learningSession->learning_target_type === 'review') {
        $maxDurationSeconds = 3600;
    }

    // 実際の学習秒数を入れる変数
    // まずは実際に経過した秒数を入れる
    $durationSeconds = $rawDurationSeconds;

    // 上限時間を超えている場合は、上限時間に丸める
    // 画面を開きっぱなしにして、異常に長い学習時間になるのを防ぐため
    if ($durationSeconds > $maxDurationSeconds) {
        $durationSeconds = $maxDurationSeconds;
    }

    // この学習セッションで回答履歴があるか確認する
    // 回答している場合は、10秒未満でも「学習した」とみなすため
    $hasAnswered = QuestionAttempt::where('learning_session_id', $learningSession->id)
        ->where('user_id', $user->id)
        ->exists();

    // 10秒未満、かつ、まだ1問も回答していない場合は、学習時間を0秒にする
    // 誤タップや即離脱を学習時間として集計しないため
    if ($rawDurationSeconds < 10 && ! $hasAnswered) {
        $durationSeconds = 0;
    }

    // 10秒未満でも回答済みの場合は、duration_seconds を0秒にしない
    // 回答していれば、短時間でも学習したと判断するため
    // 例：8秒で回答して終了した場合、duration_seconds は 8 秒になる

    // 学習セッションを終了する
    // finish_reason には completed や abandoned などが入る想定
    $learningSession->update([
        'status' => $request->input('finish_reason'),
        'ended_at' => $now,
        'last_activity_at' => $now,
        'duration_seconds' => $durationSeconds,
        ]);

    return response()->json([
        'message' => '学習セッションを終了しました。',
        ]);
    }

    public function heartbeat(Request $request, int $learning_session_id): JsonResponse
    {
    $user = Auth::user();

    $learningSession = LearningSession::where('id', $learning_session_id)
        ->where('user_id', $user->id)
        ->first();

    if (! $learningSession) {
        return response()->json(['message' => '学習セッションが見つかりません。'], 404);
    }

    if ($learningSession->status !== 'in_progress') {
        return response()->json(['message' => 'この学習セッションはすでに終了しています。'], 409);
    }

    $learningSession->update([
        'last_activity_at' => CarbonImmutable::now(),
    ]);

    return response()->json([
        'learning_session_id' => $learningSession->id,
    ]);
    }
}