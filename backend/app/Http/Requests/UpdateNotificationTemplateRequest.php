<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        $template = $this->route(
            'notification_template'
        );

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'notification_templates',
                    'code'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'project_id',
                                $project->id
                            )
                    )
                    ->ignore($template->id),
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'channel' => [
                'sometimes',
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
                'sometimes',
                'required',
                'string',
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }
}