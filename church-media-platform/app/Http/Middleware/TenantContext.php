<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class TenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);
        
        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found or invalid'
            ], 404);
        }

        if (!$tenant->isActive()) {
            return response()->json([
                'message' => 'Tenant account is inactive'
            ], 403);
        }

        // Store tenant in app container for global access
        App::instance('tenant', $tenant);
        
        // Set tenant context in request for easy access
        $request->merge(['tenant' => $tenant]);
        
        // Validate user belongs to tenant if authenticated
        if ($request->user() && $request->user()->tenant_id !== $tenant->id) {
            return response()->json([
                'message' => 'User does not belong to this tenant'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Resolve tenant from request
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        // First try to get tenant from X-Tenant header (for API requests)
        if ($tenantId = $request->header('X-Tenant')) {
            return Tenant::find($tenantId);
        }

        // Try subdomain extraction
        if ($subdomain = $this->extractSubdomain($request)) {
            return Tenant::where('subdomain', $subdomain)->first();
        }

        // Try tenant from route parameter
        if ($tenantParam = $request->route('tenant')) {
            if (is_string($tenantParam)) {
                return Tenant::where('subdomain', $tenantParam)
                    ->orWhere('id', $tenantParam)
                    ->first();
            }
            return $tenantParam instanceof Tenant ? $tenantParam : null;
        }

        // For localhost development, use demo tenant as fallback
        if (config('app.env') === 'local' && $request->getHost() === 'localhost') {
            return Tenant::where('subdomain', 'demo-church')->first();
        }

        return null;
    }

    /**
     * Extract subdomain from request
     */
    private function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        
        // Skip for local development and admin domains
        $skipDomains = ['localhost', '127.0.0.1', 'admin.tech.forworship.org'];
        if (in_array($host, $skipDomains)) {
            return null;
        }

        // Extract subdomain from host
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}