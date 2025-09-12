<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Events can be submitted by anonymous users (TV apps)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = app()->bound('tenant') ? app('tenant')->id : null;
        
        return [
            'video_id' => [
                'nullable',
                'uuid',
                Rule::exists('videos', 'id')->where(function ($query) use ($tenantId) {
                    if ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }
                }),
            ],
            'platform' => 'required|string|in:roku,appletv,firetv,web',
            'event_type' => 'required|string|in:play,pause,complete,seek,error',
            'seconds' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
            'occurred_at' => 'required|date',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'video_id.exists' => 'The specified video does not exist for this tenant.',
            'platform.in' => 'The platform must be one of: roku, appletv, firetv, web.',
            'event_type.in' => 'The event type must be one of: play, pause, complete, seek, error.',
            'seconds.min' => 'The seconds value cannot be negative.',
            'occurred_at.date' => 'The occurred at field must be a valid date.',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Set tenant_id from current tenant context
        if (app()->bound('tenant') && app('tenant')) {
            $this->merge([
                'tenant_id' => app('tenant')->id
            ]);
        }

        // Convert metadata from JSON string if needed
        if ($this->has('metadata') && is_string($this->metadata)) {
            $this->merge([
                'metadata' => json_decode($this->metadata, true) ?? []
            ]);
        }

        // Set default occurred_at if not provided
        if (!$this->has('occurred_at')) {
            $this->merge([
                'occurred_at' => now()->toISOString()
            ]);
        }
    }
}
