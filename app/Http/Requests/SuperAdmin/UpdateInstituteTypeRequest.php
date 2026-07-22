<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstituteTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('institute_types', 'name')->ignore($this->route('institute_type'))],
            'modules' => ['array'],
            'modules.*' => ['integer', Rule::exists('modules', 'id')],
        ];
    }
}
