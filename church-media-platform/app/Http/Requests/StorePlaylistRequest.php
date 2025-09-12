<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaylistRequest extends FormRequest
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
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('playlists')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($this->playlist),
            ],
            'description' => 'nullable|string|max:5000',
            'thumbnail_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|string|in:published,draft,archived',
            'video_ids' => 'nullable|array',
            'video_ids.*' => 'uuid|exists:videos,id',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'A playlist with this slug already exists for this tenant.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'status.in' => 'The status must be one of: published, draft, archived.',
            'video_ids.*.exists' => 'One or more selected videos do not exist.',
            'video_ids.*.uuid' => 'Video IDs must be valid UUIDs.',
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Generate slug from title if not provided
        if (!$this->has('slug') && $this->has('title')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title)
            ]);
        }
    }
}
