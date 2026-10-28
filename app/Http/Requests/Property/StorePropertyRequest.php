<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use App\Enums\AreaUnit;
use App\Enums\ConditionStatus;
use App\Enums\Furnishing;
use App\Enums\PropertyPurpose;
use App\Support\PropertySpecs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mews\Purifier\Facades\Purifier;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('properties.create');
    }

    /**
     * The description arrives as HTML from the rich-text editor, so strip
     * anything outside the allowed tag set before it is validated or stored.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('description')) {
            $this->merge([
                'description' => Purifier::clean($this->input('description'), 'listing'),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'images.*.uploaded' => 'Each photo must be 2MB or smaller.',
            'images.*.max' => 'Each photo must be 2MB or smaller.',
            'images.*.dimensions' => 'Each photo must be at least 400x400 pixels.',
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'propertyTypeId' => ['required', 'exists:property_types,id'],
            'locationId' => ['required', 'exists:locations,id'],
            'fullLocation' => ['nullable', 'string', 'max:500'],
            'purpose' => ['required', Rule::enum(PropertyPurpose::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999999999'],
            'negotiable' => ['boolean'],
            'areaValue' => ['required', 'numeric', 'min:0.01'],
            'areaUnit' => ['required', Rule::enum(AreaUnit::class)],
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
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:2048', 'dimensions:min_width=400,min_height=400'],
            'metaTitle' => ['nullable', 'string', 'max:70'],
            'metaDescription' => ['nullable', 'string', 'max:160'],
            'specs' => ['nullable', 'array'],
            // Fields the lister defines themselves, beyond the schema.
            'customFields' => ['nullable', 'array', 'max:20'],
            'customFields.*.label' => ['required', 'string', 'max:60'],
            'customFields.*.kind' => ['required', 'in:text,number,checkbox'],
            'customFields.*.value' => ['nullable', 'string', 'max:120'],
        ] + PropertySpecs::rules($this->integer('propertyTypeId'));
    }

    /**
     * Type-specific fields, cast to the kinds config/property_schema.php
     * declares and stripped of anything that type does not accept.
     *
     * @return array<string, mixed>
     */
    public function specs(): array
    {
        return PropertySpecs::coerce(
            $this->input('specs', []) ?? [],
            $this->integer('propertyTypeId'),
        );
    }

    /**
     * Custom fields, trimmed and stripped of rows with no label, so an
     * empty row left on the form does not reach the listing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function customFields(): array
    {
        $rows = [];

        foreach ($this->input('customFields', []) ?? [] as $row) {
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $kind = $row['kind'] ?? 'text';
            $value = $row['value'] ?? '';

            $rows[] = [
                'label' => $label,
                'kind' => $kind,
                // A checkbox is on or off; the others keep what was typed.
                'value' => $kind === 'checkbox'
                    ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    : trim((string) $value),
            ];
        }

        return $rows;
    }
}
