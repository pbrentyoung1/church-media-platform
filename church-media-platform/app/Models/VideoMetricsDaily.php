<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoMetricsDaily extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'video_id',
        'date',
        'views',
        'watch_time_seconds',
        'completions',
        'completion_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'watch_time_seconds' => 'integer',
        'completions' => 'integer',
        'completion_rate' => 'decimal:2',
    ];

    /**
     * Get the tenant that owns the metrics
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the video these metrics belong to
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get formatted watch time
     */
    public function getFormattedWatchTimeAttribute(): string
    {
        $seconds = $this->watch_time_seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Scope metrics for a specific date range
     */
    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Scope metrics for current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereYear('date', now()->year)
                    ->whereMonth('date', now()->month);
    }

    /**
     * Scope metrics for current year
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('date', now()->year);
    }

    /**
     * Boot the model - global scopes removed to prevent infinite recursion
     */
    protected static function booted(): void
    {
        static::creating(function ($metrics) {
            if (!$metrics->tenant_id && app()->bound('tenant')) {
                $tenant = app('tenant');
                if ($tenant) {
                    $metrics->tenant_id = $tenant->id;
                }
            }
        });
    }
}