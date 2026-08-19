<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'permission' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'regex:/[A-Za-z]/'],
            'duration_value' => ['required', 'integer', 'min:1'],
            'duration_unit' => ['required', 'in:Weeks,Months,Years'],
            'fee' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Course name cannot be numbers only — include at least one letter.',
        ];
    }

    /**
     * Collapses the number + unit duration fields into the single
     * "6 Months" string the courses.duration column stores.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        $unit = $validated['duration_value'] == 1 ? rtrim($validated['duration_unit'], 's') : $validated['duration_unit'];
        $duration = "{$validated['duration_value']} {$unit}";

        unset($validated['duration_value'], $validated['duration_unit']);
        $validated['duration'] = $duration;

        return $validated;
    }
}
