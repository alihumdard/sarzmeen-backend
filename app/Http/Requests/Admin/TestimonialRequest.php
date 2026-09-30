<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:60'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'purchase' => ['required', 'string', 'max:255'],
            'quote' => ['required', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'featured' => ['boolean'],
        ];
    }
}
