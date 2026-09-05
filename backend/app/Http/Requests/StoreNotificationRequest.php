<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => [
                'required',
                'string',
                'max:100',
            ],

            'channel' => [
                'required',
                'string',
                Rule::in([
                    'email',
                    'webhook',
                ]),
            ],

            'recipient' => [
                'required',
                'string',
                'max:500',
                Rule::when(
                    $this->input('channel') === 'email',
                    ['email']
                ),
                Rule::when(
                    $this->input('channel') === 'webhook',
                    ['url']
                ),
            ],

            'template' => [
                'nullable',
                'string',
                'max:100',
            ],

            'data' => [
                'nullable',
                'array',
            ],

            'scheduled_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'event_type.required' => '通知事件類型為必填。',
            'event_type.max' => '通知事件類型最多 100 個字元。',

            'channel.required' => '通知管道為必填。',
            'channel.in' => '通知管道目前只支援 email 或 webhook。',

            'recipient.required' => '通知接收者為必填。',
            'recipient.max' => '通知接收者最多 500 個字元。',

            'template.max' => '通知範本代碼最多 100 個字元。',

            'data.array' => '通知資料必須為物件或陣列格式。',

            'scheduled_at.date' => '排程時間格式錯誤。',
            'scheduled_at.after' => '排程時間必須晚於目前時間。',
        ];
    }
}