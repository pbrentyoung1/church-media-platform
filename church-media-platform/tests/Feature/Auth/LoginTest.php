<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TenantTestCase;

class LoginTest extends TenantTestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $this->startTiming();
        
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');
        
        $this->assertAuthenticatedAs($user);
        $this->assertResponseTimeUnder(200);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_login_to_different_tenant()
    {
        // Create user for different tenant
        $otherTenant = Tenant::factory()->create(['subdomain' => 'otherchurch']);
        $userFromOtherTenant = User::factory()->forTenant($otherTenant)->create([
            'email' => 'user@otherchurch.com',
            'password' => Hash::make('password123'),
        ]);

        // Try to login to current tenant with other tenant's user
        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'user@otherchurch.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_is_rate_limited_after_multiple_failed_attempts()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders($this->withTenantHeaders())
                ->post('/login', [
                    'email' => 'test@example.com',
                    'password' => 'wrong-password',
                ]);
            
            $response->assertStatus(302);
        }

        // 6th attempt should be rate limited
        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        
        // Error message should mention rate limiting
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('throttle', strtolower($errors[0]));
    }

    /** @test */
    public function user_can_login_with_remember_me()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
                'remember' => true,
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/dashboard');
        
        $this->assertAuthenticatedAs($user);
        
        // Check for remember token cookie
        $response->assertCookie('remember_web_' . sha1(config('app.name')));
    }

    /** @test */
    public function login_requires_tenant_context()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Request without tenant context should fail
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(404); // Tenant not found
    }

    /** @test */
    public function login_fails_for_inactive_tenant()
    {
        // Create inactive tenant
        $inactiveTenant = Tenant::factory()->inactive()->create(['subdomain' => 'inactivechurch']);
        $user = User::factory()->forTenant($inactiveTenant)->create([
            'email' => 'test@inactive.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withHeaders([
            'Host' => 'inactivechurch.forworship.com',
            'X-Tenant' => $inactiveTenant->id,
        ])->post('/login', [
            'email' => 'test@inactive.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403); // Tenant inactive
    }

    /** @test */
    public function user_redirected_to_2fa_setup_when_required()
    {
        // Create tenant that requires 2FA
        $tenant = Tenant::factory()->requires2FA()->create(['subdomain' => '2fachurch']);
        $user = User::factory()->forTenant($tenant)->create([
            'email' => 'admin@2fachurch.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('admin'); // Admin role requires 2FA

        $response = $this->withHeaders([
            'Host' => '2fachurch.forworship.com',
            'X-Tenant' => $tenant->id,
        ])->post('/login', [
            'email' => 'admin@2fachurch.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/two-factor-setup');
    }

    /** @test */
    public function user_redirected_to_2fa_challenge_when_enabled()
    {
        $user = User::factory()
            ->forTenant($this->tenant)
            ->withTwoFactor()
            ->create([
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
            ]);

        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302)
            ->assertRedirect('/two-factor-challenge');
    }

    /** @test */
    public function logout_works_correctly()
    {
        $user = User::factory()->forTenant($this->tenant)->create();
        
        $this->actingAs($user)
            ->withHeaders($this->withTenantHeaders())
            ->post('/logout');

        $this->assertGuest();
    }

    /** @test */
    public function login_activity_is_logged()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        // Check that activity was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => 'user_login',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiters before each test
        RateLimiter::clear('login');
    }
}