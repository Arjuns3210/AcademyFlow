<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'permission' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z\s.\'-]+$/'],
            'qualification' => ['nullable', 'string', 'max:150'],
            // Same shape as Student's parent_mobile: optional country code,
            // then a 10-digit number.
            'mobile' => ['required', 'string', 'regex:/^(\+?\d{1,3}[-\s]?)?\d{10}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'subjects' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', Auth::user()->tenant_id),
                Rule::unique('teachers', 'user_id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'That staff login is already linked to another teacher.',
            'name.regex' => 'Teacher name may only contain letters, spaces, apostrophes, and hyphens.',
        ];
    }
}
