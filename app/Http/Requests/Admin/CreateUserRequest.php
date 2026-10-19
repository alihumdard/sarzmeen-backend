<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'role' => ['required', Rule::in([
                UserRole::User->value,
                UserRole::Agent->value,
                UserRole::Agency->value,
                UserRole::Admin->value,
            ])],

            // An agency account needs a name for the agency itself.
            'agencyName' => ['required_if:role,agency', 'nullable', 'string', 'max:255'],

            // An agent may be attached to an agency, or left independent.
            'agencyId' => ['nullable', 'exists:agencies,id'],

            'title' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'agencyName.required_if' => 'An agency account needs an agency name.',
        ];
    }
}
