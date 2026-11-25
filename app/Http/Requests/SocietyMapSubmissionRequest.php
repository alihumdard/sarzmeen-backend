<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A society owner offering their map.
 *
 * Open to anybody, signed in or not: the people who hold these maps are
 * society offices and developers, who are not users of the site.
 */
class SocietyMapSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'societyName' => ['required', 'string', 'max:160'],
            'cityName' => ['nullable', 'string', 'max:100'],

            /* Their claim about approval, not the site's. Nullable
               because "I don't know" is an honest answer and a forced
               yes/no would just produce guesses. */
            'nocApproved' => ['nullable', 'boolean'],
            'nocAuthority' => ['nullable', 'string', 'max:120'],

            'contactName' => ['required', 'string', 'max:120'],
            'contactPhone' => ['required', 'string', 'max:32'],
            'contactEmail' => ['nullable', 'email', 'max:160'],
            'contactRole' => ['nullable', 'string', 'max:64'],

            'notes' => ['nullable', 'string', 'max:2000'],

            /* Optional, and small: it only has to be recognisable on an
               admin screen, not zoomed into. */
            'photoFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            /* 12MB is what PHP accepts here (upload_max_filesize); asking
               for more would fail in the server before this rule ran. */
            'imageFile' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:12288',
            ],
            'documentFile' => ['nullable', 'file', 'mimes:pdf', 'max:12288'],
        ];
    }

    public function messages(): array
    {
        return [
            'societyName.required' => 'Please name the society this map is of.',
            'contactPhone.required' => 'A phone number is needed so we can reach you about the map.',
            'imageFile.required' => 'Please attach the map image.',
            'imageFile.max' => 'The map image may be up to 12MB.',
            'documentFile.max' => 'The PDF may be up to 12MB.',
            'photoFile.max' => 'Your photo may be up to 4MB.',
        ];
    }
}
