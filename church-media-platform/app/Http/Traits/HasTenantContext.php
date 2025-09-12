<?php

namespace App\Http\Traits;

use App\Services\TenantService;
use Illuminate\Support\Facades\Auth;

trait HasTenantContext
{
    /**
     * Get shared tenant data for Inertia responses
     */
    protected function getTenantShareData(): array
    {
        $tenant = TenantService::current();
        $user = Auth::user();
        
        if (!$tenant) {
            return [];
        }

        return [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
                'branding' => $tenant->getBrandingConfig(),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getTenantRoles(),
                    'two_factor_enabled' => $user->two_factor_enabled,
                ] : null,
            ],
        ];
    }

    /**
     * Get user permissions for the current tenant
     */
    protected function getUserPermissions(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        return [
            'can_manage_videos' => $user->can('manage videos') || $user->hasRole(['admin', 'editor']),
            'can_manage_playlists' => $user->can('manage playlists') || $user->hasRole(['admin', 'editor']),
            'can_manage_events' => $user->can('manage events') || $user->hasRole(['admin', 'editor']),
            'can_manage_users' => $user->can('manage users') || $user->hasRole(['admin']),
            'can_manage_settings' => $user->can('manage settings') || $user->hasRole(['admin']),
            'can_view_analytics' => $user->can('view analytics') || $user->hasRole(['admin', 'editor']),
            'can_delete_videos' => $user->can('delete videos') || $user->hasRole(['admin']),
            'can_publish_events' => $user->can('publish events') || $user->hasRole(['admin', 'editor']),
            'can_manage_branding' => $user->can('manage branding') || $user->hasRole(['admin']),
        ];
    }

    /**
     * Merge tenant context with Inertia props
     */
    protected function withTenantContext(array $props = []): array
    {
        return array_merge($props, [
            'shared' => $this->getTenantShareData(),
            'permissions' => $this->getUserPermissions(),
        ]);
    }
}