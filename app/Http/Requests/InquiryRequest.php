<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
            'propertyId' => ['nullable', 'exists:properties,id'],
            'projectId' => ['nullable', 'exists:projects,id'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if (is_array($data)) {
            if (isset($data['propertyId'])) {
                $data['property_id'] = $data['propertyId'];
                unset($data['propertyId']);
            }
            if (isset($data['projectId'])) {
                $data['project_id'] = $data['projectId'];
                unset($data['projectId']);
            }
        }

        return $data;
    }
}
