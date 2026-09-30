<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use App\Enums\AreaUnit;
use App\Enums\ConditionStatus;
use App\Enums\Furnishing;
use App\Enums\PropertyPurpose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'propertyTypeId' => ['sometimes', 'required', 'exists:property_types,id'],
            'locationId' => ['sometimes', 'required', 'exists:locations,id'],
            'fullLocation' => ['nullable', 'string', 'max:500'],
            'purpose' => ['sometimes', 'required', Rule::enum(PropertyPurpose::class)],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999999999'],
            'negotiable' => ['boolean'],
            'areaValue' => ['sometimes', 'required', 'numeric', 'min:0'],
            'areaUnit' => ['sometimes', 'required', Rule::enum(AreaUnit::class)],
            'beds' => ['nullable', 'integer', 'min:0', 'max:99'],
            'baths' => ['nullable', 'integer', 'min:0', 'max:99'],
            'livingRooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'kitchens' => ['nullable', 'integer', 'min:0', 'max:99'],
            'carParking' => ['nullable', 'integer', 'min:0', 'max:99'],
            'floors' => ['nullable', 'integer', 'min:0', 'max:99'],
            'furnishing' => ['nullable', Rule::enum(Furnishing::class)],
            'propertyCondition' => ['nullable', Rule::enum(ConditionStatus::class)],
            'features' => ['nullable', 'array', 'max:30'],
            'features.*' => ['string', 'max:100'],
            'nearbyPlaces' => ['nullable', 'array', 'max:20'],
            'nearbyPlaces.*.name' => ['required', 'string', 'max:255'],
            'nearbyPlaces.*.distance' => ['required', 'string', 'max:50'],
            'nearbyPlaces.*.kind' => ['required', 'in:park,road,airport,mall'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'status' => ['sometimes', 'in:draft,pending'],
        ];
    }
}
