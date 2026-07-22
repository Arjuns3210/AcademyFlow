<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('role')),
            ],
            'permissions' => ['array'],
            'permissions.*.view' => ['sometimes', 'boolean'],
            'permissions.*.add' => ['sometimes', 'boolean'],
            'permissions.*.edit' => ['sometimes', 'boolean'],
            'permissions.*.delete' => ['sometimes', 'boolean'],
            'permissions.*.status' => ['sometimes', 'boolean'],
        ];
    }
}
