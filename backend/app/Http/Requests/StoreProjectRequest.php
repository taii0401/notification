<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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

            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:projects,slug',
            ],

            'status' => [
                'nullable',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '專案名稱為必填。',
            'name.max' => '專案名稱最多 100 個字元。',

            'slug.required' => '專案識別名稱為必填。',
            'slug.alpha_dash' => 'Slug 只能包含英文字母、數字、破折號與底線。',
            'slug.unique' => '此 Slug 已經存在。',

            'status.in' => '狀態只能是 active 或 inactive。',
        ];
    }
}