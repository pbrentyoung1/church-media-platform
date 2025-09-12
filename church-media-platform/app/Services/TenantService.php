<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\App;

class TenantService
{
    /**
     * Get the current tenant from app container
     */
    public static function current(): ?Tenant
    {
        try {
            return App::get('tenant');
        } catch (\Illuminate\Container\EntryNotFoundException $e) {
            return null;
        }
    }

    /**
     * Check if tenant context is available
     */
    public static function hasTenant(): bool
    {
        return self::current() !== null;
    }

    /**
     * Get tenant ID safely
     */
    public static function id(): ?string
    {
        return self::current()?->id;
    }

    /**
     * Get tenant subdomain safely
     */
    public static function subdomain(): ?string
    {
        return self::current()?->subdomain;
    }

    /**
     * Check if current tenant is active
     */
    public static function isActive(): bool
    {
        return self::current()?->isActive() ?? false;
    }

    /**
     * Get tenant feature settings
     */
    public static function getFeatureSetting(string $key, mixed $default = null): mixed
    {
        $settings = self::current()?->feature_settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Get tenant branding settings
     */
    public static function getBrandingSetting(string $key, mixed $default = null): mixed
    {
        $settings = self::current()?->branding_settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Set tenant in app container
     */
    public static function set(Tenant $tenant): void
    {
        App::instance('tenant', $tenant);
    }

    /**
     * Clear tenant from app container
     */
    public static function clear(): void
    {
        App::forgetInstance('tenant');
    }
}