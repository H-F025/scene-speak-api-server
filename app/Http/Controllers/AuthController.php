<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
}
