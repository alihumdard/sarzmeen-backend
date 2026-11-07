<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /* On an update the row keeps its own slug, so ignore it in the check. */
        $id = $this->route('service')?->id;

        return [
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('services', 'slug')->ignore($id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'outcome' => ['required', 'string', 'max:255'],
            'summary' => ['required', 'string', 'max:2000'],
            'points' => ['required', 'array', 'min:1', 'max:12'],
            'points.*' => ['required', 'string', 'max:500'],
            'icon' => ['required', 'string', Rule::in(Service::ICONS)],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'featured' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may use lowercase letters, numbers and hyphens only.',
            'points.min' => 'List at least one thing the client receives.',
        ];
    }
}
