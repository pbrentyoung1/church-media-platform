<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TenantTestCase;

class TwoFactorAuthTest extends TenantTestCase
{
    use RefreshDatabase;

    private Google2FA $google2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->google2fa = new Google2FA();
    }

    /** @test */
    public function user_can_enable_two_factor_authentication()
    {
        $user = User::factory()->forTenant($this->tenant)->create();

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->post('/user/two-factor-authentication');

        $response->assertStatus(302);
        
        // Refresh user from database
        $user->refresh();
        
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at); // Not confirmed yet
    }

    /** @test */
    public function user_can_view_qr_code_after_enabling_2fa()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->get('/user/two-factor-qr-code');

        $response->assertStatus(200)
            ->assertJsonStructure(['svg', 'url']);

        $qrData = $response->json();
        $this->assertStringContainsString('data:image/svg+xml', $qrData['svg']);
        $this->assertStringContainsString('otpauth://totp/', $qrData['url']);
        $this->assertStringContainsString($this->tenant->name, $qrData['url']);
    }

    /** @test */
    public function user_can_confirm_two_factor_authentication()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $user = User::factory()->forTenant($this->tenant)->create([
            'two_factor_secret' => $secret,
        ]);

        // Generate valid TOTP code
        $validCode = $this->google2fa->getCurrentOtp($secret);

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->post('/user/confirmed-two-factor-authentication', [
                'code' => $validCode,
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');

        // Refresh user from database
        $user->refresh();
        
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->two_factor_enabled);
        $this->assertNotNull($user->two_factor_recovery_codes);
    }

    /** @test */
    public function user_cannot_confirm_2fa_with_invalid_code()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $user = User::factory()->forTenant($this->tenant)->create([
            'two_factor_secret' => $secret,
        ]);

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->post('/user/confirmed-two-factor-authentication', [
                'code' => '123456', // Invalid code
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('code');

        // User should not be confirmed
        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
    }

    /** @test */
    public function user_can_verify_2fa_challenge_with_totp_code()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create([
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'two_factor_secret' => $secret,
            ]);

        // First login to get to challenge page
        $loginResponse = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        $loginResponse->assertRedirect('/two-factor-challenge');

        // Generate valid TOTP code
        $validCode = $this->google2fa->getCurrentOtp($secret);

        // Verify with TOTP code
        $response = $this->withSession(['login.id' => $user->id])
            ->withHeaders($this->withTenantHeaders())
            ->post('/two-factor-challenge', [
                'code' => $validCode,
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');
        
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_can_verify_2fa_challenge_with_recovery_code()
    {
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create([
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
            ]);

        // Verify with recovery code
        $response = $this->withSession(['login.id' => $user->id])
            ->withHeaders($this->withTenantHeaders())
            ->post('/two-factor-challenge', [
                'recovery_code' => 'recovery-code-1',
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');
        
        $this->assertAuthenticatedAs($user);

        // Recovery code should be removed after use
        $user->refresh();
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $this->assertNotContains('recovery-code-1', $recoveryCodes);
    }

    /** @test */
    public function user_cannot_verify_2fa_with_invalid_recovery_code()
    {
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create();

        $response = $this->withSession(['login.id' => $user->id])
            ->withHeaders($this->withTenantHeaders())
            ->post('/two-factor-challenge', [
                'recovery_code' => 'invalid-recovery-code',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    /** @test */
    public function user_can_generate_new_recovery_codes()
    {
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create();

        $oldRecoveryCodes = $user->two_factor_recovery_codes;

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->post('/user/two-factor-recovery-codes');

        $response->assertStatus(200)
            ->assertJsonStructure(['recovery_codes']);

        // New codes should be different
        $user->refresh();
        $this->assertNotEquals($oldRecoveryCodes, $user->two_factor_recovery_codes);
    }

    /** @test */
    public function user_can_disable_two_factor_authentication()
    {
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create();

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->delete('/user/two-factor-authentication');

        $response->assertStatus(302);

        // 2FA should be disabled
        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    /** @test */
    public function admin_user_cannot_disable_2fa_when_required_by_tenant()
    {
        $tenant = Tenant::factory()->requires2FA()->create(['subdomain' => '2fachurch']);
        $user = User::factory()
            ->forTenant($tenant)
            ->withTwoFactor()
            ->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->withHeaders([
                'Host' => '2fachurch.forworship.com',
                'X-Tenant' => $tenant->id,
            ])
            ->delete('/user/two-factor-authentication');

        $response->assertStatus(302);
        $response->assertSessionHasErrors('two_factor');

        // 2FA should still be enabled
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
    }

    /** @test */
    public function admin_user_cannot_disable_2fa_when_required_by_role()
    {
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create();
        $user->assignRole('admin'); // Admin role requires 2FA

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->delete('/user/two-factor-authentication');

        $response->assertStatus(302);
        $response->assertSessionHasErrors('two_factor');

        // 2FA should still be enabled
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
    }

    /** @test */
    public function editor_user_can_disable_2fa_when_not_required()
    {
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create();
        $user->assignRole('editor'); // Editor role doesn't require 2FA

        $response = $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->delete('/user/two-factor-authentication');

        $response->assertStatus(302);

        // 2FA should be disabled
        $user->refresh();
        $this->assertNull($user->two_factor_secret);
    }

    /** @test */
    public function two_factor_setup_page_shows_tenant_requirements()
    {
        $tenant = Tenant::factory()->requires2FA()->create(['subdomain' => '2fachurch']);
        $user = User::factory()->forTenant($tenant)->create();

        $response = $this->actingAs($user)
            ->withHeaders([
                'Host' => '2fachurch.forworship.com',
                'X-Tenant' => $tenant->id,
            ])
            ->get('/two-factor-setup');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->has('tenant')
                ->where('tenant.requires_2fa', true)
        );
    }

    /** @test */
    public function activity_is_logged_for_2fa_operations()
    {
        $user = User::factory()->forTenant($this->tenant)->create();

        // Enable 2FA
        $this->actingAsTenantUser($user)
            ->withHeaders($this->withTenantHeaders())
            ->post('/user/two-factor-authentication');

        // Check activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => 'two_factor_enabled',
        ]);
    }
}