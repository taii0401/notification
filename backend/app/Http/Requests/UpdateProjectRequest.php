<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('projects', 'slug')
                    ->ignore($project->id),
            ],

            'status' => [
                'sometimes',
                'required',
                'in:active,inactive',
            ],
        ];
    }
}