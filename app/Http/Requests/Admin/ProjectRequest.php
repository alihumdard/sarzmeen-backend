<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('project')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('projects')->ignore($projectId)],
            'projectCategoryId' => ['required', 'exists:project_categories,id'],
            'developer' => ['required', 'string', 'max:255'],
            'locationId' => ['required', 'exists:locations,id'],
            'fullLocation' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'max:50'],
            'priceFrom' => ['nullable', 'string', 'max:100'],
            'totalArea' => ['nullable', 'string', 'max:100'],
            'totalUnits' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'verified' => ['boolean'],
            'featured' => ['boolean'],
            'amenities' => ['nullable', 'array', 'max:30'],
            'amenities.*' => ['string', 'max:100'],
            'highlights' => ['nullable', 'array', 'max:20'],
            'highlights.*' => ['string', 'max:500'],
            'plotSizes' => ['nullable', 'array', 'max:20'],
            'plotSizes.*' => ['string', 'max:50'],
            'paymentPlan' => ['nullable', 'array', 'max:20'],
            'paymentPlan.*.label' => ['required', 'string', 'max:100'],
            'paymentPlan.*.value' => ['required', 'string', 'max:100'],
            'images' => ['nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'metaTitle' => ['nullable', 'string', 'max:70'],
            'metaDescription' => ['nullable', 'string', 'max:160'],
        ];
    }
}
