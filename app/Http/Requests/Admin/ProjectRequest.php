<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\InventoryCategory;
use App\Enums\PaymentType;
use App\Enums\UnitAvailability;
use App\Enums\ProjectFormat;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
        $projectId = $this->route('project')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('projects')->ignore($projectId)],
            'projectCategoryId' => ['required', 'exists:project_categories,id'],
            'developer' => ['required', 'string', 'max:255'],
            'locationId' => ['required', 'exists:locations,id'],
            'fullLocation' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'projectType' => ['required', Rule::enum(ProjectType::class)],
            'projectFormat' => ['required', Rule::enum(ProjectFormat::class)],
            'paymentType' => ['required', Rule::enum(PaymentType::class)],

            'towersCount' => ['nullable', 'integer', 'min:0', 'max:999'],
            'totalFloors' => ['nullable', 'integer', 'min:0', 'max:999'],
            'basementFloors' => ['nullable', 'integer', 'min:0', 'max:99'],
            'parkingSpaces' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'expectedCompletion' => ['nullable', 'string', 'max:100'],
            'possessionTimeline' => ['nullable', 'string', 'max:100'],
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
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:2048', 'dimensions:min_width=400,min_height=400'],
            'metaTitle' => ['nullable', 'string', 'max:70'],
            'metaDescription' => ['nullable', 'string', 'max:160'],
            'towers' => ['nullable', 'array'],
            'towers.*.name' => ['required_with:towers', 'string', 'max:255'],
            'towers.*.floorsCount' => ['nullable', 'integer', 'min:0', 'max:300'],
            'towers.*.unitsCount' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'towers.*.description' => ['nullable', 'string', 'max:2000'],
            'towers.*.floors' => ['nullable', 'array'],
            // Signed: basements are negative, ground is 0.
            'towers.*.floors.*.floorNumber' => ['required_with:towers.*.floors', 'integer', 'min:-20', 'max:300'],
            'towers.*.floors.*.name' => ['nullable', 'string', 'max:255'],
            'towers.*.floors.*.description' => ['nullable', 'string', 'max:1000'],
            'focusKeyword' => ['nullable', 'string', 'max:255'],
            'canonicalUrl' => ['nullable', 'string', 'max:500'],
            'ogTitle' => ['nullable', 'string', 'max:255'],
            'ogDescription' => ['nullable', 'string', 'max:500'],
            'ogImage' => ['nullable', 'string', 'max:500'],

            'developerId' => ['nullable', 'exists:developers,id'],

            'faqs' => ['nullable', 'array', 'max:30'],
            'faqs.*.question' => ['required', 'string', 'max:255'],
            'faqs.*.answer' => ['required', 'string', 'max:2000'],

            'floorPlans' => ['nullable', 'array', 'max:50'],
            'floorPlans.*.name' => ['required', 'string', 'max:255'],
            'floorPlans.*.floorNumber' => ['nullable', 'string', 'max:50'],
            'floorPlans.*.tower' => ['nullable', 'string', 'max:100'],
            'floorPlans.*.description' => ['nullable', 'string', 'max:1000'],

            'units' => ['nullable', 'array', 'max:30'],
            'units.*.unitType' => ['required', 'string', 'max:100'],
            'units.*.unitName' => ['nullable', 'string', 'max:255'],
            'units.*.category' => ['nullable', Rule::enum(InventoryCategory::class)],
            'units.*.area' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'units.*.minArea' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'units.*.maxArea' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'units.*.areaUnit' => ['nullable', Rule::in(['sqft', 'sqyd', 'marla'])],
            'units.*.ratePerSqft' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'units.*.maxPrice' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'units.*.availability' => ['nullable', Rule::enum(UnitAvailability::class)],
            // Free-form because residential, commercial and hospitality units
            // carry entirely different attributes (spec sections 6-8).
            'units.*.details' => ['nullable', 'array'],

            'paymentTerms' => ['nullable', 'array'],
            'paymentTerms.pctBooking' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paymentTerms.pctConfirmation' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paymentTerms.pctMonthly' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paymentTerms.pctBiAnnual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paymentTerms.pctBalloon' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paymentTerms.pctGrey' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paymentTerms.pctPossession' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paymentTerms.monthlyCount' => ['nullable', 'integer', 'min:0', 'max:600'],
            'paymentTerms.biAnnualCount' => ['nullable', 'integer', 'min:0', 'max:100'],
            'paymentTerms.balloonCount' => ['nullable', 'integer', 'min:0', 'max:100'],
            'paymentTerms.balloonFrequency' => ['nullable', 'integer', 'min:0', 'max:60'],
            'paymentTerms.planDurationYears' => ['nullable', 'numeric', 'min:0', 'max:50'],
        ];
    }

    /**
     * Percentages must total 100% before a plan can be published — enforced
     * here and not only in the form, since the API is reachable directly.
     *
     * An all-zero set means "no plan", which stays allowed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $terms = $this->input('paymentTerms');

            if (! is_array($terms)) {
                return;
            }

            $total = collect([
                'pctBooking', 'pctConfirmation', 'pctMonthly',
                'pctBalloon', 'pctGrey', 'pctPossession',
            ])->sum(fn (string $key) => (float) ($terms[$key] ?? 0));

            if ($total === 0.0) {
                return;
            }

            // Tolerate float drift from splits such as 33.33 x 3.
            if (abs($total - 100) > 0.01) {
                $validator->errors()->add(
                    'paymentTerms',
                    sprintf('Payment percentages must equal 100%%. They currently total %s%%.', round($total, 2)),
                );
            }
        });
    }
}
