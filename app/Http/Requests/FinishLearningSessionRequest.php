<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinishLearningSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'finish_reason' => ['required', 'string', 'in:completed,abandoned'],
        ];
    }

    public function attributes(): array
    {
        return [
            'finish_reason' => '終了理由',
        ];
    }
}