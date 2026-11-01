<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TaxonomyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                // A slug with spaces or capitals saves fine and then never
                // resolves as a URL, so the shape is checked, not just uniqueness.
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent' => ['nullable', 'string', 'exists:categories,slug'],
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
