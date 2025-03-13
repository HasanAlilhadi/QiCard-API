<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function rules(): array
    {
        $guardName = config('auth.defaults.guard');

        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('roles')->where(function ($query) use ($guardName) {
                    return $query->where('guard_name', $guardName);
                })
            ],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['exists:permissions,id']
        ];
    }
}
