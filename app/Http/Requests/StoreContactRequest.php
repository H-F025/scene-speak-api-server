<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
    return [
        'name'    => ['required', 'string', 'max:50'],
        'email'   => ['required', 'string', 'email:rfc', 'max:254'],
        'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
    return [
        'name'    => 'お名前',
        'email'   => 'メールアドレス',
        'message' => 'お問い合わせ内容',
        ];
    }
}