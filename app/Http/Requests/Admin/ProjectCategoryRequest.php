<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TaxonomyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectCategoryRequest extends FormRequest
{
    /**
     * `description` is NOT NULL in the database (default ''), but the rules
     * accept null. Without this, saving a record with the description left
     * blank fails with a raw constraint-violation 500.
     */
    protected function prepareForValidation(): void
    {
        if ($this->exists('description') && $this->input('description') === null) {
            $this->merge(['description' => '']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectCategoryId = $this->route('projectCategory')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('project_categories')->ignore($projectCategoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(TaxonomyStatus::class)],
            'featured' => ['required', 'boolean'],
        ];
    }
}
