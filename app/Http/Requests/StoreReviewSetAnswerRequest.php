<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewSetAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_choice_id' => ['required', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'question_choice_id' => '回答ID',
        ];
    }
}
