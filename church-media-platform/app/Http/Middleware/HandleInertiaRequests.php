<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = Auth::user();
        $tenant = TenantService::current();

        return array_merge(parent::share($request), [
            // Authentication data
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getTenantRoles(),
                    'two_factor_enabled' => $user->two_factor_enabled,
                    'email_verified_at' => $user->email_verified_at,
                ] : null,
            ],

            // Tenant data
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
                'branding' => $tenant->getBrandingConfig(),
                'features' => $tenant->feature_settings ?? [],
            ] : null,

            // User permissions
            'permissions' => $user ? $this->getUserPermissions($user) : [],

            // Flash messages
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'status' => fn () => $request->session()->get('status'),
            ],

            // CSRF Token
            'csrf_token' => csrf_token(),

            // App configuration
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'environment' => config('app.env'),
            ],
        ]);
    }

    /**
     * Get user permissions within the current tenant
     */
    private function getUserPermissions($user): array
    {
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
}