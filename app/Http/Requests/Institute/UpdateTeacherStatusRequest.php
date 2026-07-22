<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'permission' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
