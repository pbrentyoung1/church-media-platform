<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    private TenantContext $middleware;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TenantContext();
        $this->tenant = Tenant::factory()->create([
            'subdomain' => 'testchurch',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function resolves_tenant_from_x_tenant_header()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Tenant', $this->tenant->id);

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals($this->tenant->id, App::get('tenant')->id);
            return new Response();
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function resolves_tenant_from_subdomain()
    {
        $request = Request::create('http://testchurch.forworship.com/', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals($this->tenant->id, App::get('tenant')->id);
            return new Response();
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function resolves_tenant_from_route_parameter()
    {
        $request = Request::create('/', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/', []);
            $route->setParameter('tenant', $this->tenant->subdomain);
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals($this->tenant->id, App::get('tenant')->id);
            return new Response();
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rejects_request_when_tenant_not_found()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Tenant', 'non-existent-tenant-id');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Tenant not found', $response->getContent());
    }

    /** @test */
    public function rejects_request_when_tenant_is_inactive()
    {
        $inactiveTenant = Tenant::factory()->inactive()->create(['subdomain' => 'inactive']);
        
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Tenant', $inactiveTenant->id);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('inactive', $response->getContent());
    }

    /** @test */
    public function validates_authenticated_user_belongs_to_tenant()
    {
        $user = User::factory()->forTenant($this->tenant)->create();
        $this->actingAs($user);

        $request = Request::create('/', 'GET');
        $request->headers->set('X-Tenant', $this->tenant->id);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rejects_user_from_different_tenant()
    {
        $otherTenant = Tenant::factory()->create(['subdomain' => 'otherschurch']);
        $userFromOtherTenant = User::factory()->forTenant($otherTenant)->create();
        
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Tenant', $this->tenant->id);
        $request->setUserResolver(function () use ($userFromOtherTenant) {
            return $userFromOtherTenant;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response();
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('does not belong', $response->getContent());
    }

    /** @test */
    public function skips_local_development_domains_for_subdomain_extraction()
    {
        $request = Request::create('http://localhost:8000/', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            // Should not have resolved any tenant from localhost
            return new Response();
        });

        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function extracts_subdomain_correctly_from_complex_domains()
    {
        $request = Request::create('http://my-church.forworship.com/', 'GET');
        
        $tenant = Tenant::factory()->create(['subdomain' => 'my-church']);

        $response = $this->middleware->handle($request, function ($req) use ($tenant) {
            $this->assertEquals($tenant->id, App::get('tenant')->id);
            return new Response();
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function stores_tenant_in_app_container()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Tenant', $this->tenant->id);

        $this->middleware->handle($request, function ($req) {
            // Tenant should be available in app container
            $this->assertInstanceOf(Tenant::class, App::get('tenant'));
            $this->assertEquals($this->tenant->id, App::get('tenant')->id);
            return new Response();
        });
    }

    /** @test */
    public function adds_tenant_to_request_attributes()
    {
        $request = Request::create('/', 'GET');
        $request->headers->set('X-Tenant', $this->tenant->id);

        $this->middleware->handle($request, function ($req) {
            $this->assertArrayHasKey('tenant', $req->all());
            $this->assertEquals($this->tenant->id, $req->get('tenant')->id);
            return new Response();
        });
    }

    /** @test */
    public function handles_uuid_and_subdomain_route_parameters()
    {
        $request = Request::create('/', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/', []);
            $route->setParameter('tenant', $this->tenant->id); // UUID
            return $route;
        });

        $response = $this->middleware->handle($request, function ($req) {
            $this->assertEquals($this->tenant->id, App::get('tenant')->id);
            return new Response();
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function prioritizes_header_over_subdomain()
    {
        $headerTenant = $this->tenant;
        $subdomainTenant = Tenant::factory()->create(['subdomain' => 'subdomain-church']);

        $request = Request::create('http://subdomain-church.forworship.com/', 'GET');
        $request->headers->set('X-Tenant', $headerTenant->id);

        $response = $this->middleware->handle($request, function ($req) use ($headerTenant) {
            // Should use header tenant, not subdomain tenant
            $this->assertEquals($headerTenant->id, App::get('tenant')->id);
            return new Response();
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}