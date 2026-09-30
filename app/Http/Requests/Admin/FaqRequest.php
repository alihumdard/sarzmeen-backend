<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'order' => ['required', 'integer', 'min:0'],
        ];
    }
}
