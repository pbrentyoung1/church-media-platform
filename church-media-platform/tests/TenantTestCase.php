<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TenantTestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $editorUser;
    protected User $viewerUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions and roles
        $this->createPermissionsAndRoles();
        
        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Church',
            'subdomain' => 'testchurch',
            'status' => 'active',
        ]);

        // Set tenant context
        TenantService::set($this->tenant);

        // Create test users with different roles
        $this->adminUser = User::factory()
            ->forTenant($this->tenant)
            ->create(['email' => 'admin@testchurch.com']);
        $this->adminUser->assignRole('admin');

        $this->editorUser = User::factory()
            ->forTenant($this->tenant)
            ->create(['email' => 'editor@testchurch.com']);
        $this->editorUser->assignRole('editor');

        $this->viewerUser = User::factory()
            ->forTenant($this->tenant)
            ->create(['email' => 'viewer@testchurch.com']);
        $this->viewerUser->assignRole('viewer');
    }

    protected function tearDown(): void
    {
        TenantService::clear();
        parent::tearDown();
    }

    /**
     * Create permissions and roles for testing
     */
    protected function createPermissionsAndRoles(): void
    {
        // Create permissions
        $permissions = [
            'manage videos',
            'manage playlists',
            'manage events',
            'manage users',
            'manage settings',
            'view analytics',
            'delete videos',
            'publish events',
            'manage branding',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        $editorRole = Role::create(['name' => 'editor']);
        $editorRole->givePermissionTo([
            'manage videos',
            'manage playlists',
            'manage events',
            'view analytics',
            'publish events',
        ]);

        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->givePermissionTo([
            'view analytics',
        ]);
    }

    /**
     * Act as a tenant user for requests
     */
    protected function actingAsTenantUser(User $user = null): self
    {
        $user = $user ?? $this->adminUser;
        
        // Ensure user belongs to current tenant
        if ($user->tenant_id !== $this->tenant->id) {
            throw new \InvalidArgumentException('User must belong to the current test tenant');
        }
        
        return $this->actingAs($user);
    }

    /**
     * Create a user for a different tenant (for cross-tenant testing)
     */
    protected function createUserForDifferentTenant(): User
    {
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Church',
            'subdomain' => 'otherchurch',
        ]);

        return User::factory()
            ->forTenant($otherTenant)
            ->create(['email' => 'user@otherchurch.com']);
    }

    /**
     * Set request headers to simulate tenant context from subdomain
     */
    protected function withTenantHeaders(array $headers = []): array
    {
        return array_merge([
            'Host' => $this->tenant->subdomain . '.forworship.com',
            'X-Tenant' => $this->tenant->id,
        ], $headers);
    }

    /**
     * Make authenticated API request with tenant context
     */
    protected function authenticatedApiCall(string $method, string $uri, array $data = [], User $user = null)
    {
        $user = $user ?? $this->adminUser;
        
        // Create API token
        $token = $user->createToken('test-token', ['*'])->plainTextToken;
        
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->json($method, $uri, $data);
    }

    /**
     * Assert that response has proper tenant isolation
     */
    protected function assertTenantIsolated($response): void
    {
        if ($response->getStatusCode() === 200 && $response->json()) {
            $data = $response->json();
            
            // If response contains tenant_id, it should match current tenant
            if (isset($data['tenant_id'])) {
                $this->assertEquals($this->tenant->id, $data['tenant_id']);
            }
            
            // If response contains array of items, all should belong to current tenant
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $item) {
                    if (isset($item['tenant_id'])) {
                        $this->assertEquals($this->tenant->id, $item['tenant_id']);
                    }
                }
            }
        }
    }

    /**
     * Performance assertion helper
     */
    protected function assertResponseTimeUnder(int $milliseconds): void
    {
        $executionTime = (microtime(true) - $this->startTime) * 1000;
        $this->assertLessThan($milliseconds, $executionTime, 
            "Response time {$executionTime}ms exceeded limit of {$milliseconds}ms");
    }

    /**
     * Start timing for performance tests
     */
    protected function startTiming(): void
    {
        $this->startTime = microtime(true);
    }

    private float $startTime;
}