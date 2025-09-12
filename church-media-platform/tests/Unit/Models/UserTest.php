<?php

namespace Tests\Unit\Models;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->forTenant($this->tenant)->create();
    }

    /** @test */
    public function user_belongs_to_tenant()
    {
        $this->assertInstanceOf(Tenant::class, $this->user->tenant);
        $this->assertEquals($this->tenant->id, $this->user->tenant->id);
    }

    /** @test */
    public function user_password_is_hashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotEquals('password123', $user->password);
    }

    /** @test */
    public function two_factor_enabled_attribute_works_correctly()
    {
        // User without 2FA
        $userWithout2FA = User::factory()->forTenant($this->tenant)->create([
            'two_factor_confirmed_at' => null,
        ]);

        $this->assertFalse($userWithout2FA->two_factor_enabled);

        // User with 2FA
        $userWith2FA = User::factory()->forTenant($this->tenant)->withTwoFactor()->create();

        $this->assertTrue($userWith2FA->two_factor_enabled);
    }

    /** @test */
    public function get_tenant_roles_returns_user_roles()
    {
        $this->createPermissionsAndRoles();
        
        $this->user->assignRole('admin');
        $roles = $this->user->getTenantRoles();

        $this->assertContains('admin', $roles);
        $this->assertIsArray($roles);
    }

    /** @test */
    public function has_tenant_permission_validates_user_context()
    {
        $this->createPermissionsAndRoles();
        
        $this->user->assignRole('admin');

        // Mock auth user context
        $this->actingAs($this->user);

        $this->assertTrue($this->user->hasTenantPermission('manage videos'));
        $this->assertFalse($this->user->hasTenantPermission('nonexistent permission'));
    }

    /** @test */
    public function scope_tenant_filters_users_by_current_tenant()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->forTenant($otherTenant)->create();

        $this->actingAs($this->user);

        $tenantUsers = User::tenant()->get();

        $this->assertCount(1, $tenantUsers);
        $this->assertEquals($this->user->id, $tenantUsers->first()->id);
        $this->assertFalse($tenantUsers->contains($otherUser));
    }

    /** @test */
    public function global_scope_applies_tenant_isolation()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->forTenant($otherTenant)->create();

        $this->actingAs($this->user);

        $allUsers = User::all();

        // Should only return users from current tenant
        $this->assertCount(1, $allUsers);
        $this->assertEquals($this->user->id, $allUsers->first()->id);
    }

    /** @test */
    public function creating_user_automatically_sets_tenant_id()
    {
        $this->actingAs($this->user);

        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertEquals($this->tenant->id, $newUser->tenant_id);
    }

    /** @test */
    public function user_model_uses_uuid_primary_key()
    {
        $this->assertIsString($this->user->id);
        $this->assertEquals(36, strlen($this->user->id)); // UUID length
        $this->assertStringContainsString('-', $this->user->id); // UUID format
    }

    /** @test */
    public function two_factor_fields_are_hidden_in_serialization()
    {
        $user = User::factory()->forTenant($this->tenant)->withTwoFactor()->create();

        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $userArray);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $userArray);
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    /** @test */
    public function two_factor_enabled_is_appended_to_array()
    {
        $user = User::factory()->forTenant($this->tenant)->withTwoFactor()->create();

        $userArray = $user->toArray();

        $this->assertArrayHasKey('two_factor_enabled', $userArray);
        $this->assertTrue($userArray['two_factor_enabled']);
    }

    /** @test */
    public function user_dates_are_cast_correctly()
    {
        $user = User::factory()->forTenant($this->tenant)->withTwoFactor()->create([
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->two_factor_confirmed_at);
    }

    /** @test */
    public function user_password_is_cast_as_hashed()
    {
        $plainPassword = 'test-password';
        $user = User::factory()->forTenant($this->tenant)->create([
            'password' => $plainPassword,
        ]);

        // Password should be automatically hashed
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    /** @test */
    public function user_implements_must_verify_email()
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class, $this->user);
    }

    /** @test */
    public function user_uses_required_traits()
    {
        $traits = class_uses_recursive(User::class);

        $this->assertArrayHasKey(\Illuminate\Database\Eloquent\Concerns\HasUuids::class, $traits);
        $this->assertArrayHasKey(\Laravel\Sanctum\HasApiTokens::class, $traits);
        $this->assertArrayHasKey(\Laravel\Fortify\TwoFactorAuthenticatable::class, $traits);
        $this->assertArrayHasKey(\Spatie\Permission\Traits\HasRoles::class, $traits);
    }

    /** @test */
    public function user_fillable_includes_required_fields()
    {
        $fillable = (new User())->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('email_verified_at', $fillable);
    }

    /** @test */
    public function user_can_have_api_tokens_with_scopes()
    {
        $token = $this->user->createToken('test-token', ['catalog:read', 'events:write']);

        $this->assertNotNull($token->plainTextToken);
        $this->assertIsArray($token->accessToken->abilities);
        $this->assertContains('catalog:read', $token->accessToken->abilities);
        $this->assertContains('events:write', $token->accessToken->abilities);
    }

    private function createPermissionsAndRoles(): void
    {
        $permissions = [
            'manage videos',
            'manage playlists',
            'manage events',
            'manage users',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::create(['name' => $permission]);
        }

        $adminRole = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);
    }
}