<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BlogStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $blogId = $this->route('blog')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('blogs', 'slug')->ignore($blogId),
            ],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'readTime' => ['nullable', 'integer', 'min:1', 'max:120'],
            'status' => ['required', Rule::enum(BlogStatus::class)],
            'featured' => ['boolean'],
            'category' => ['nullable', 'exists:blog_categories,slug'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'exists:blog_tags,slug'],
        ];
    }
}
