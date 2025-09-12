<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Playlist extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'description',
        'thumbnail_url',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the tenant that owns the playlist
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the videos in this playlist
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'playlist_items')
                    ->withPivot('sort_order')
                    ->withTimestamps()
                    ->orderBy('pivot_sort_order');
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate slug from title
     */
    public function generateSlug(): string
    {
        $baseSlug = Str::slug($this->title);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('tenant_id', $this->tenant_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $this->id)
                    ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if playlist is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Scope a query to only include published playlists
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Boot the model - global scopes removed to prevent infinite recursion
     */
    protected static function booted(): void
    {
        static::creating(function ($playlist) {
            if (!$playlist->tenant_id && app()->bound('tenant')) {
                $tenant = app('tenant');
                if ($tenant) {
                    $playlist->tenant_id = $tenant->id;
                }
            }
            
            if (!$playlist->slug) {
                $playlist->slug = $playlist->generateSlug();
            }
        });

        static::updating(function ($playlist) {
            if ($playlist->isDirty('title') && !$playlist->isDirty('slug')) {
                $playlist->slug = $playlist->generateSlug();
            }
        });
    }
}
