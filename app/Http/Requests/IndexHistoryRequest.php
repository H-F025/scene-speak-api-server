<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexHistoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'year_month' => ['sometimes', 'date_format:Y-m'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'year_month' => '対象月',
        ];
    }
}
