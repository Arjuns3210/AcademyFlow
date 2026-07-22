<?php

namespace App\Http\Requests\SuperAdmin;

use App\Support\ThemeColorPresets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstituteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institute = $this->route('institute');
        $allowedColors = ThemeColorPresets::colors();

        // An institute already on a custom color set before the preset
        // palette existed must still be able to resubmit that exact value
        // unchanged — saving some unrelated field on this form (contact
        // number, a module toggle, ...) shouldn't force a color change or
        // silently reset it to the default. No *other* non-preset value is
        // allowed in, just this institute's own current one.
        if ($institute && $institute->theme_color && ! in_array($institute->theme_color, $allowedColors, true)) {
            $allowedColors[] = $institute->theme_color;
        }

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('institutes', 'name')->ignore($this->route('institute'))],
            'institute_type_id' => ['required', Rule::exists('institute_types', 'id')],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'theme_color' => ['nullable', Rule::in($allowedColors)],
            'address' => ['nullable', 'string', 'max:500'],
            'contact' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'gst_number' => ['nullable', 'string', 'max:50'],

            'modules' => ['array'],
            'modules.*' => ['integer', Rule::exists('modules', 'id')],
            'custom_labels' => ['array'],
            'custom_labels.*' => ['nullable', 'string', 'max:100'],
        ];
    }
}
