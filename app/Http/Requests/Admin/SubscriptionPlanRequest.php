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
            'slug' => ['required', 'string', 'max:100', Rule::unique('subscription_plans')->ignore($planId)],
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
}
