<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'video_id',
        'platform',
        'event_type',
        'seconds',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'seconds' => 'integer',
    ];

    /**
     * Get the tenant that owns the event
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the video associated with the event
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Scope events by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope events by platform
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope events within date range
     */
    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    /**
     * Get play events
     */
    public function scopePlayEvents($query)
    {
        return $query->where('event_type', 'play');
    }

    /**
     * Get completion events
     */
    public function scopeCompletionEvents($query)
    {
        return $query->where('event_type', 'complete');
    }

    /**
     * Boot the model - global scopes removed to prevent infinite recursion
     */
    protected static function booted(): void
    {
        static::creating(function ($event) {
            if (!$event->tenant_id && app()->bound('tenant')) {
                $tenant = app('tenant');
                if ($tenant) {
                    $event->tenant_id = $tenant->id;
                }
            }
        });
    }
}