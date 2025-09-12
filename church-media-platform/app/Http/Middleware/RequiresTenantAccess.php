<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresTenantAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = TenantService::current();

        // Must be authenticated
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'));
        }

        // Must have tenant context
        if (!$tenant) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tenant context required.'], 400);
            }
            abort(400, 'Tenant context required');
        }

        // User must belong to the current tenant
        if ($user->tenant_id !== $tenant->id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. User does not belong to this tenant.'], 403);
            }
            abort(403, 'Access denied. User does not belong to this tenant.');
        }

        // Tenant must be active
        if (!$tenant->isActive()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tenant account is inactive.'], 403);
            }
            abort(403, 'Tenant account is inactive.');
        }

        return $next($request);
    }
}