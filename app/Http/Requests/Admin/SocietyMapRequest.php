<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BlogStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocietyMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('society_maps.manage') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('society_map')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],

            /* One map per society: a second would leave no way to say
               which one the society's page shows. */
            'locationId' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'integer',
                'exists:locations,id',
                Rule::unique('society_maps', 'location_id')->ignore($id),
            ],

            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],

            /* The map itself. Larger than the site's other uploads on
               purpose: a society map is read by zooming into it, and one
               squeezed to 2MB loses the plot numbers that make it worth
               publishing. It is stored at full size for the same reason.

               12MB is what PHP accepts here (upload_max_filesize); asking
               for more would fail in the server before this rule ran, and
               the uploader would see an empty request, not a message. */
            'imageFile' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:12288',
            ],

            /* An optional PDF for the download button. */
            'documentFile' => ['nullable', 'file', 'mimes:pdf', 'max:12288'],
            'removeDocument' => ['nullable', 'boolean'],

            'status' => ['required', Rule::enum(BlogStatus::class)],
            'featured' => ['nullable', 'boolean'],
            'sortOrder' => ['nullable', 'integer', 'min:0'],

            'blogIds' => ['nullable', 'array'],
            'blogIds.*' => ['integer', 'exists:blogs,id'],

            'metaTitle' => ['nullable', 'string', 'max:255'],
            'metaDescription' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'locationId.unique' => 'That society already has a map. Edit the existing one instead.',
            'imageFile.max' => 'The map image may be up to 12MB.',
            'documentFile.max' => 'The PDF may be up to 12MB.',
        ];
    }
}
