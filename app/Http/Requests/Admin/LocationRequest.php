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
            'slug' => ['required', 'string', 'max:255', Rule::unique('locations')->ignore($locationId)],
            'type' => ['required', Rule::enum(LocationType::class)],
            'parent' => ['nullable', 'string', 'exists:locations,slug'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::enum(TaxonomyStatus::class)],
            'featured' => ['required', 'boolean'],
        ];
    }
}
