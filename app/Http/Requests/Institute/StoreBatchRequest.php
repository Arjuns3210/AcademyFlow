<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
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
            'time_slot_start' => ['required', 'date_format:H:i'],
            'time_slot_end' => ['required', 'date_format:H:i', 'after:time_slot_start'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Collapses the two time-picker fields into the single "4:00 PM -
     * 5:30 PM" string the batches.time_slot column stores.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        $start = Carbon::createFromFormat('H:i', $validated['time_slot_start'])->format('g:i A');
        $end = Carbon::createFromFormat('H:i', $validated['time_slot_end'])->format('g:i A');

        unset($validated['time_slot_start'], $validated['time_slot_end']);
        $validated['time_slot'] = "{$start} - {$end}";

        return $validated;
    }
}
