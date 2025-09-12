<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    // Middleware is applied in routes, not in constructor for Laravel 12

    /**
     * Show the two-factor authentication setup page
     */
    public function setup(): Response
    {
        $user = Auth::user();
        $tenant = TenantService::current();
        
        return Inertia::render('Auth/TwoFactorSetup', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'two_factor_enabled' => $user->two_factor_enabled,
            ],
            'tenant' => [
                'name' => $tenant->name,
                'requires_2fa' => TenantService::getFeatureSetting('require_2fa', false),
            ],
        ]);
    }

    /**
     * Show the two-factor authentication challenge page
     */
    public function challenge(): Response
    {
        if (!session('login.id')) {
            return redirect()->route('login');
        }

        $tenant = TenantService::current();
        
        return Inertia::render('Auth/TwoFactorChallenge', [
            'tenant' => [
                'name' => $tenant->name,
                'branding' => $tenant->getBrandingConfig(),
            ],
        ]);
    }

    /**
     * Enable two-factor authentication for the user
     */
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $user = $request->user();
        
        $enable($user);
        
        // Log 2FA enabling
        activity()
            ->performedOn($user)
            ->withProperties([
                'tenant_id' => TenantService::id(),
                'ip_address' => $request->ip(),
            ])
            ->log('two_factor_enabled');

        return back()->with('status', 'Two-factor authentication has been enabled.');
    }

    /**
     * Get the QR code SVG for two-factor authentication setup
     */
    public function qrCode(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->two_factor_secret) {
            return response()->json(['error' => 'Two-factor authentication must be enabled first.'], 400);
        }

        $tenant = TenantService::current();
        $google2fa = new Google2FA();
        
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            $tenant->name,
            $user->email,
            $user->two_factor_secret
        );

        return response()->json([
            'svg' => (new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            ))->render($qrCodeUrl),
            'url' => $qrCodeUrl,
        ]);
    }

    /**
     * Confirm two-factor authentication setup
     */
    public function confirm(ConfirmTwoFactorRequest $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $user = $request->user();
        
        try {
            $confirm($user, $request->input('code'));
            
            // Log 2FA confirmation
            activity()
                ->performedOn($user)
                ->withProperties([
                    'tenant_id' => TenantService::id(),
                    'ip_address' => $request->ip(),
                ])
                ->log('two_factor_confirmed');

            return redirect()->route('dashboard')->with('status', 'Two-factor authentication has been confirmed and enabled.');
            
        } catch (ValidationException $e) {
            return back()->withErrors(['code' => 'The provided code was invalid.']);
        }
    }

    /**
     * Get recovery codes for the user
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->two_factor_enabled) {
            return response()->json(['error' => 'Two-factor authentication is not enabled.'], 400);
        }

        return response()->json([
            'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
        ]);
    }

    /**
     * Generate new recovery codes
     */
    public function newRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->two_factor_enabled) {
            return response()->json(['error' => 'Two-factor authentication is not enabled.'], 400);
        }

        $generate($user);
        
        // Log recovery code generation
        activity()
            ->performedOn($user)
            ->withProperties([
                'tenant_id' => TenantService::id(),
                'ip_address' => $request->ip(),
            ])
            ->log('recovery_codes_generated');

        return response()->json([
            'recovery_codes' => json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true),
        ]);
    }

    /**
     * Disable two-factor authentication
     */
    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $user = $request->user();
        $tenant = TenantService::current();
        
        // Check if 2FA is required for this user/tenant
        $tenantRequires2FA = TenantService::getFeatureSetting('require_2fa', false);
        $roleRequires2FA = $user->hasRole(['admin', 'super-admin']);
        
        if ($tenantRequires2FA || $roleRequires2FA) {
            return back()->withErrors(['two_factor' => 'Two-factor authentication is required and cannot be disabled.']);
        }
        
        $disable($user);
        
        // Log 2FA disabling
        activity()
            ->performedOn($user)
            ->withProperties([
                'tenant_id' => TenantService::id(),
                'ip_address' => $request->ip(),
            ])
            ->log('two_factor_disabled');

        return back()->with('status', 'Two-factor authentication has been disabled.');
    }

    /**
     * Verify the two-factor authentication challenge
     */
    public function verify(TwoFactorChallengeRequest $request): RedirectResponse
    {
        if (!session('login.id')) {
            throw ValidationException::withMessages([
                'code' => ['The provided code was invalid.'],
            ]);
        }

        $user = \App\Models\User::findOrFail(session('login.id'));
        
        if ($code = $request->input('code')) {
            $valid = $this->verifyTotpCode($user, $code);
        } elseif ($recoveryCode = $request->input('recovery_code')) {
            $valid = $this->verifyRecoveryCode($user, $recoveryCode);
        } else {
            $valid = false;
        }

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => ['The provided code was invalid.'],
            ]);
        }

        Auth::login($user, session('login.remember', false));
        
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();
        
        // Log successful 2FA verification
        activity()
            ->performedOn($user)
            ->withProperties([
                'tenant_id' => TenantService::id(),
                'ip_address' => $request->ip(),
                'method' => $code ? 'totp' : 'recovery_code',
            ])
            ->log('two_factor_verified');

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Verify TOTP code
     */
    private function verifyTotpCode($user, string $code): bool
    {
        $google2fa = new Google2FA();
        
        return $google2fa->verifyKey($user->two_factor_secret, $code);
    }

    /**
     * Verify recovery code
     */
    private function verifyRecoveryCode($user, string $recoveryCode): bool
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        
        if (in_array($recoveryCode, $recoveryCodes)) {
            // Remove the used recovery code
            $recoveryCodes = array_diff($recoveryCodes, [$recoveryCode]);
            
            // Update user's recovery codes
            $user->forceFill([
                'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
            ])->save();
            
            return true;
        }
        
        return false;
    }
}