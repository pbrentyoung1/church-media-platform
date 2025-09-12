<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'subdomain',
        'email',
        'status',
        'branding_settings',
        'feature_settings',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'branding_settings' => 'array',
        'feature_settings' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Get all users for this tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all videos for this tenant
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get all playlists for this tenant
     */
    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    /**
     * Get all events for this tenant
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get all device links for this tenant
     */
    public function deviceLinks(): HasMany
    {
        return $this->hasMany(DeviceLink::class);
    }

    /**
     * Get video metrics for this tenant
     */
    public function videoMetrics(): HasMany
    {
        return $this->hasMany(VideoMetricsDaily::class);
    }

    /**
     * Get branding configuration for TV apps
     */
    public function getBrandingConfig(): array
    {
        $settings = $this->branding_settings ?? [];
        
        return [
            'logo_url' => $settings['logo_url'] ?? null,
            'primary_color' => $settings['primary_color'] ?? '#1f2937',
            'secondary_color' => $settings['secondary_color'] ?? '#6b7280',
            'roku_theme_json' => $this->generateRokuTheme($settings),
        ];
    }

    /**
     * Generate Roku-specific theme configuration
     */
    private function generateRokuTheme(array $settings): array
    {
        return [
            'primaryColor' => $settings['primary_color'] ?? '#1f2937',
            'secondaryColor' => $settings['secondary_color'] ?? '#6b7280',
            'backgroundColor' => $settings['background_color'] ?? '#ffffff',
            'textColor' => $settings['text_color'] ?? '#000000',
            'logoUrl' => $settings['logo_url'] ?? null,
        ];
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'subdomain';
    }
}
