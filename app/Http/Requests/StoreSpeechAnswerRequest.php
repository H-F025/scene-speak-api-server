<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpeechAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transcript' => ['required', 'string', 'max:1000'],
            'learning_session_id' => ['nullable', 'integer', 'exists:learning_sessions,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'transcript' => '回答内容',
            'learning_session_id' => '学習セッションID',
        ];
    }
}
