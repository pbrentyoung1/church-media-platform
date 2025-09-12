<?php

namespace App\Http\Controllers;

use App\Http\Traits\HasTenantContext;
use App\Services\TenantService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

abstract class TenantAwareController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, HasTenantContext;

    // Middleware is applied in routes, not in constructor for Laravel 12

    /**
     * Render Inertia response with tenant context
     */
    protected function renderWithTenant(string $component, array $props = []): Response
    {
        return Inertia::render($component, $this->withTenantContext($props));
    }

    /**
     * Get the current authenticated user with tenant validation
     */
    protected function getAuthenticatedUser()
    {
        $user = Auth::user();
        $tenant = TenantService::current();
        
        if ($user && $tenant && $user->tenant_id !== $tenant->id) {
            abort(403, 'User does not belong to the current tenant context');
        }
        
        return $user;
    }

    /**
     * Ensure the current request has proper tenant context
     */
    protected function ensureTenantContext(): void
    {
        if (!TenantService::hasTenant()) {
            abort(404, 'Tenant context required');
        }
    }

    /**
     * Log tenant-aware activity
     */
    protected function logTenantActivity(string $description, array $properties = [], $subject = null): void
    {
        $user = Auth::user();
        $tenant = TenantService::current();
        
        $activity = activity($description);
        
        if ($subject) {
            $activity->performedOn($subject);
        }
        
        if ($user) {
            $activity->causedBy($user);
        }
        
        $activity->withProperties(array_merge($properties, [
            'tenant_id' => $tenant?->id,
            'tenant_subdomain' => $tenant?->subdomain,
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]))->log();
    }

    /**
     * Handle tenant-aware validation errors
     */
    protected function handleTenantValidationError(Request $request, array $errors): void
    {
        $tenant = TenantService::current();
        
        $this->logTenantActivity('validation_error', [
            'errors' => $errors,
            'request_data' => $request->except(['password', 'password_confirmation', '_token']),
        ]);
        
        if ($request->expectsJson()) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json(['errors' => $errors], 422)
            );
        }
        
        redirect()->back()->withErrors($errors)->withInput($request->except('password'));
    }

    /**
     * Get tenant-specific flash messages
     */
    protected function getTenantFlashMessages(): array
    {
        $tenant = TenantService::current();
        
        return [
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info'),
            'status' => session('status'),
            'tenant_message' => session("tenant_{$tenant?->id}_message"),
        ];
    }
}