<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        $guardName = config('auth.defaults.guard');

        return [
            'name' => ['sometimes', 'string', 'max:255',
                Rule::unique('permissions')
                    ->where('guard_name', $guardName)
                    ->ignore($this->route('permission'))
            ],
            'group' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
