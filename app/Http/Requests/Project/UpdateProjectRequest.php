<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status'      => ['sometimes', 'string', 'in:active,archived'],
        ];
    }
}
