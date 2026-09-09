<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'code' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'notification_templates',
                    'code'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'project_id',
                            $project->id
                        )
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'channel' => [
                'required',
                Rule::in([
                    'email',
                    'webhook',
                ]),
            ],

            'subject' => [
                'nullable',
                'string',
                'max:255',
            ],

            'content' => [
                'required',
                'string',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }
}