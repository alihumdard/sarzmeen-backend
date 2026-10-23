<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BlogStatus;
use App\Models\Video;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('video')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('videos', 'slug')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],

            // Checked by shape below, since only a YouTube link is usable.
            'youtubeUrl' => ['required', 'string', 'max:500'],

            'thumbnail' => ['nullable', 'string', 'max:500'],
            'thumbnailFile' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=400,min_height=400',
            ],

            'status' => ['required', Rule::enum(BlogStatus::class)],
            'featured' => ['boolean'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'metaTitle' => ['nullable', 'string', 'max:70'],
            'metaDescription' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $url = (string) $this->input('youtubeUrl', '');

            if ($url !== '' && Video::extractYoutubeId($url) === null) {
                $validator->errors()->add(
                    'youtubeUrl',
                    'Enter a YouTube link, for example https://www.youtube.com/watch?v=xxxxxxxxxxx',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The permalink may only contain lowercase letters, numbers and hyphens.',
            'thumbnailFile.max' => 'The thumbnail must be 2MB or smaller.',
            'thumbnailFile.uploaded' => 'The thumbnail must be 2MB or smaller.',
            'thumbnailFile.dimensions' => 'The thumbnail must be at least 400x400 pixels.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('description') && $this->input('description') === null) {
            $this->merge(['description' => '']);
        }
    }
}
