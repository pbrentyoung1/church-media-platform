<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    // Middleware is applied in routes, not in constructor for Laravel 12

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $tenant = TenantService::current();
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'tenant' => [
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'branding' => $tenant->getBrandingConfig(),
            ],
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
            
            $request->session()->regenerate();
            
            $user = Auth::user();
            $tenant = TenantService::current();
            
            // Log successful login
            activity()
                ->performedOn($user)
                ->withProperties([
                    'tenant_id' => $tenant->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('user_login');

            // Check if user requires 2FA
            if ($this->requiresTwoFactorAuthentication($user, $tenant)) {
                return $this->handleTwoFactorRedirect($request);
            }

            return redirect()->intended(route('dashboard'));
            
        } catch (\Exception $e) {
            // Log failed login attempt
            activity()
                ->withProperties([
                    'tenant_id' => TenantService::id(),
                    'email' => $request->input('email'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'error' => $e->getMessage(),
                ])
                ->log('login_failed');
            
            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $tenant = TenantService::current();

        // Log logout
        if ($user) {
            activity()
                ->performedOn($user)
                ->withProperties([
                    'tenant_id' => $tenant?->id,
                    'ip_address' => $request->ip(),
                ])
                ->log('user_logout');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Check if user requires two-factor authentication
     */
    private function requiresTwoFactorAuthentication($user, $tenant): bool
    {
        // Check tenant-level 2FA requirement
        $tenantRequires2FA = TenantService::getFeatureSetting('require_2fa', false);
        
        // Check role-based 2FA requirement
        $roleRequires2FA = $user->hasRole(['admin', 'super-admin']);
        
        return ($tenantRequires2FA || $roleRequires2FA) && !$user->two_factor_enabled;
    }

    /**
     * Handle two-factor authentication redirect
     */
    private function handleTwoFactorRedirect(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        if ($user->two_factor_enabled) {
            // User has 2FA enabled, redirect to challenge
            return redirect()->route('two-factor.challenge');
        } else {
            // User needs to set up 2FA
            return redirect()->route('two-factor.setup')
                ->with('status', 'Two-factor authentication is required for your account.');
        }
    }
}