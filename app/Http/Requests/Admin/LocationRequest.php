<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\LocationType;
use App\Enums\TaxonomyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locationId = $this->route('location')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                // A slug with spaces or capitals saves fine and then never
                // resolves as a URL, so the shape is checked, not just uniqueness.
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('locations')->ignore($locationId),
            ],
            'type' => ['required', Rule::enum(LocationType::class)],
            'parent' => ['nullable', 'string', 'exists:locations,slug'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::enum(TaxonomyStatus::class)],
            'featured' => ['required', 'boolean'],
        ];
    }


    public function messages(): array

    {

        return [

            'slug.regex' => 'The slug may only contain lowercase letters, numbers and hyphens.',

        ];

    }
}
