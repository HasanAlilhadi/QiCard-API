<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        $guardName = config('auth.defaults.guard');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')->where('guard_name', $guardName)],
            'group' => ['required', 'string', 'max:255'],
        ];
    }
}
