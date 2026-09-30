<?php

declare(strict_types=1);

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class InviteAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('agents.invite');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'title' => ['nullable', 'string', 'max:100'],
        ];
    }
}
