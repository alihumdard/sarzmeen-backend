<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                // A slug with spaces or capitals saves fine and then never
                // resolves as a URL, so the shape is checked, not just uniqueness.
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('subscription_plans')->ignore($planId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'durationDays' => ['required', 'integer', 'min:1', 'max:365'],
            'listingLimit' => ['required', 'integer', 'min:1', 'max:500'],
            'featuredLimit' => ['required', 'integer', 'min:0', 'max:100'],
            'imageLimit' => ['required', 'integer', 'min:1', 'max:50'],
            'prioritySupport' => ['boolean'],
            'analyticsAccess' => ['boolean'],
            'isActive' => ['boolean'],
            'sortOrder' => ['nullable', 'integer', 'min:0'],
        ];
    }


    public function messages(): array

    {

        return [

            'slug.regex' => 'The slug may only contain lowercase letters, numbers and hyphens.',

        ];

    }
}
