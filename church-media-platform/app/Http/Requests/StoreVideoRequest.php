<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'duration' => 'nullable|integer|min:0',
            'source' => 'required|string|in:vimeo,youtube,resi,upload',
            'external_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('videos')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId)
                                ->where('source', $this->source);
                })->ignore($this->video),
            ],
            'playback_url' => 'nullable|url|max:500',
            'thumbnail_url' => 'nullable|url|max:500',
            'captions_url' => 'nullable|url|max:500',
            'status' => 'required|string|in:published,draft,archived',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'external_id.unique' => 'A video with this external ID already exists for this tenant and source.',
            'source.in' => 'The source must be one of: vimeo, youtube, resi, upload.',
            'status.in' => 'The status must be one of: published, draft, archived.',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('metadata') && is_string($this->metadata)) {
            $this->merge([
                'metadata' => json_decode($this->metadata, true) ?? []
            ]);
        }
    }
}
