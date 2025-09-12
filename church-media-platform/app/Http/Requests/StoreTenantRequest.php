<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create', \App\Models\Tenant::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:50|alpha_dash|unique:tenants,subdomain',
            'email' => 'required|email|max:255|unique:tenants,email',
            'status' => 'required|string|in:active,suspended,trial',
            'branding_settings' => 'nullable|array',
            'feature_settings' => 'nullable|array',
            'trial_ends_at' => 'nullable|date|after:now',
            'subscription_ends_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'subdomain.unique' => 'This subdomain is already taken.',
            'subdomain.alpha_dash' => 'The subdomain may only contain letters, numbers, dashes, and underscores.',
            'email.unique' => 'This email address is already associated with another tenant.',
            'status.in' => 'The status must be one of: active, suspended, trial.',
        ];
    }
}
