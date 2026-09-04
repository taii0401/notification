<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'API Key 名稱為必填。',
            'name.max' => 'API Key 名稱最多 100 個字元。',
            'expires_at.date' => 'API Key 過期時間格式錯誤。',
            'expires_at.after' => 'API Key 過期時間必須晚於目前時間。',
        ];
    }
}