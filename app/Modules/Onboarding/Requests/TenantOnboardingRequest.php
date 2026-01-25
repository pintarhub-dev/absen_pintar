<?php

namespace App\Modules\Onboarding\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantOnboardingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'required|string|max:10|unique:tenants,code',
            'full_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|digits_between:1,20',
            'address' => 'required|string',
        ];
    }
}
