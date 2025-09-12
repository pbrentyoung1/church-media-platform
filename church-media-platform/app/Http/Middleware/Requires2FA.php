<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Requires2FA
{
    /**
     * Handle an incoming request.
     * 
     * @param array|string $roles Comma-separated string or array of roles that require 2FA
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Must be authenticated
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'));
        }

        // If no roles specified, require 2FA for all users
        if (empty($roles)) {
            if (!$user->two_factor_enabled) {
                return $this->redirect2FASetup($request);
            }
            return $next($request);
        }

        // Check if user has any of the specified roles
        $userHasRequiredRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $userHasRequiredRole = true;
                break;
            }
        }

        // If user has required role but no 2FA, redirect to setup
        if ($userHasRequiredRole && !$user->two_factor_enabled) {
            return $this->redirect2FASetup($request);
        }

        // If user has required role and has 2FA, or doesn't have required role, continue
        return $next($request);
    }

    /**
     * Redirect to 2FA setup page
     */
    private function redirect2FASetup(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication is required for this action.',
                'redirect' => route('two-factor.setup')
            ], 403);
        }

        return redirect()->route('two-factor.setup')
            ->with('warning', 'Two-factor authentication is required for this action.');
    }
}