<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'digits:6'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('code') && !$this->filled('recovery_code')) {
                $validator->errors()->add(
                    'code', 
                    'Please provide either a TOTP code or recovery code.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.digits' => 'The code must be exactly 6 digits.',
            'code.string' => 'The code must be a string.',
            'recovery_code.string' => 'The recovery code must be a string.',
        ];
    }
}