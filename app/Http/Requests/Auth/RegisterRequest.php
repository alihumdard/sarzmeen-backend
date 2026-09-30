<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTOs\RegisterData;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in([
                UserRole::User->value,
                UserRole::Agent->value,
                UserRole::Agency->value,
            ])],
            'phone' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'agencyName' => ['required_if:role,agency', 'nullable', 'string', 'max:255'],
            'agencyType' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDTO(): RegisterData
    {
        return new RegisterData(
            name: $this->validated('name'),
            email: $this->validated('email'),
            password: $this->validated('password'),
            role: UserRole::from($this->validated('role')),
            phone: $this->validated('phone'),
            city: $this->validated('city'),
            agencyName: $this->validated('agencyName'),
            agencyType: $this->validated('agencyType'),
            title: $this->validated('title'),
        );
    }
}
