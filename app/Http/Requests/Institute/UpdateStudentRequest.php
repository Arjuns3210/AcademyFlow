<?php

namespace App\Http\Requests\Institute;

use App\Models\Batch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_mobile' => ['required', 'string', 'regex:/^(\+?\d{1,3}[-\s]?)?\d{10}$/'],
            'parent_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'admission_date' => ['nullable', 'date'],
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

                    if ($batch && $batch->isFull(excludingStudentId: $this->route('student')->id)) {
                        $fail("This batch is at full capacity ({$batch->capacity}).");
                    }
                },
            ],
        ];
    }
}
