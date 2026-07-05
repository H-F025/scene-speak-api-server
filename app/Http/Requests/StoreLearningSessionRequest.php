<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLearningSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'learning_type' => ['required', 'string', 'in:normal,review'],
            'learning_target_id' => ['required', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'learning_type' => '学習種別',
            'learning_target_id' => '学習対象ID',
        ];
    }
}
