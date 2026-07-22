<?php

namespace App\Http\Requests\SuperAdmin;

use App\Support\ThemeColorPresets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstituteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('institutes', 'name')],
            'institute_type_id' => ['required', Rule::exists('institute_types', 'id')],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'theme_color' => ['nullable', Rule::in(ThemeColorPresets::colors())],
            'address' => ['nullable', 'string', 'max:500'],
            'contact' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'gst_number' => ['nullable', 'string', 'max:50'],

            'modules' => ['array'],
            'modules.*' => ['integer', Rule::exists('modules', 'id')],
            'custom_labels' => ['array'],
            'custom_labels.*' => ['nullable', 'string', 'max:100'],

            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
