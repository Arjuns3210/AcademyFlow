<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'permission' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'course_id' => [
                'required',
                'integer',
                Rule::exists('courses', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
            'time_slot' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
