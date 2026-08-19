<?php

namespace App\Http\Requests\Institute;

use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other'],
            'student_mobile' => ['nullable', 'string', 'regex:/^(\+?\d{1,3}[-\s]?)?\d{10}$/'],
            'parent_name' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z\s.\'-]+$/'],
            // Loosely matches an optional country code (with an optional
            // dash/space separator) followed by a 10-digit mobile number —
            // e.g. "9876543210", "+91-9876543210", "91 9876543210".
            'parent_mobile' => [
                'required',
                'string',
                'regex:/^(\+?\d{1,3}[-\s]?)?\d{10}$/',
                Rule::unique('students')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', Auth::user()->tenant_id)
                        ->where('name', $this->input('name'))
                        ->whereNull('deleted_at')),
            ],
            'parent_email' => ['nullable', 'email', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'admission_date' => ['required', 'date'],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
            'batch_id' => [
                'nullable',
                'integer',
                Rule::exists('batches', 'id')
                    ->where('tenant_id', Auth::user()->tenant_id)
                    ->where('course_id', $this->input('course_id')),
                function ($attribute, $value, $fail) {
                    $batch = $value ? Batch::find($value) : null;

                    if ($batch && $batch->isFull()) {
                        $fail("This batch is at full capacity ({$batch->capacity}).");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Student name may only contain letters, spaces, apostrophes, and hyphens.',
            'parent_name.regex' => 'Parent name may only contain letters, spaces, apostrophes, and hyphens.',
            'parent_mobile.unique' => 'A student with this name and parent mobile number already exists.',
        ];
    }
}
