<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexThemeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'english_level' => ['sometimes', 'string', 'in:beginner,intermediate,advanced'],
        ];
    }

    public function messages(): array
    {
        return [
            'english_level.in' => '英語レベルはbeginner、intermediate、advancedのいずれかを指定してください。',
        ];
    }
}
