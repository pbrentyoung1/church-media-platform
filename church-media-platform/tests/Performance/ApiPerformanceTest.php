<?php

namespace Tests\Performance;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class ApiPerformanceTest extends TenantTestCase
{
    use RefreshDatabase;

    private const PERFORMANCE_THRESHOLD_MS = 200;

    /** @test */
    public function catalog_api_responds_under_200ms_with_large_dataset()
    {
        // Create large dataset
        Video::factory()->count(1000)->published()->forTenant($this->tenant)->create();
        
        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['catalog:read']);

        $this->startTiming();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog?per_page=50');

        $response->assertStatus(200);
        $this->assertResponseTimeUnder(self::PERFORMANCE_THRESHOLD_MS);
        
        $data = $response->json('data');
        $this->assertLessThanOrEqual(50, count($data));
    }

    /** @test */
    public function branding_api_responds_under_200ms()
    {
        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['branding:read']);

        $this->startTiming();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(200);
        $this->assertResponseTimeUnder(self::PERFORMANCE_THRESHOLD_MS);
    }

    /** @test */
    public function search_api_responds_under_200ms()
    {
        // Create searchable content
        Video::factory()->count(500)->published()->forTenant($this->tenant)->create();
        
        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['catalog:read']);

        $this->startTiming();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/search?q=test');

        $response->assertStatus(200);
        $this->assertResponseTimeUnder(self::PERFORMANCE_THRESHOLD_MS);
    }

    /** @test */
    public function authentication_endpoints_respond_under_200ms()
    {
        $user = User::factory()->forTenant($this->tenant)->create([
            'email' => 'perf@test.com',
            'password' => bcrypt('password123'),
        ]);

        $this->startTiming();

        $response = $this->withHeaders($this->withTenantHeaders())
            ->post('/login', [
                'email' => 'perf@test.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302);
        $this->assertResponseTimeUnder(self::PERFORMANCE_THRESHOLD_MS);
    }

    /** @test */
    public function concurrent_api_requests_maintain_performance()
    {
        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['catalog:read']);

        Video::factory()->count(100)->published()->forTenant($this->tenant)->create();

        $times = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token->plainTextToken,
                'Accept' => 'application/json',
                'X-Tenant' => $this->tenant->id,
            ])->get('/api/v1/catalog?page=' . ($i + 1));
            
            $times[] = (microtime(true) - $start) * 1000;
            
            $response->assertStatus(200);
        }

        $averageTime = array_sum($times) / count($times);
        $maxTime = max($times);

        $this->assertLessThan(self::PERFORMANCE_THRESHOLD_MS, $averageTime, 
            "Average response time {$averageTime}ms exceeded threshold");
        $this->assertLessThan(self::PERFORMANCE_THRESHOLD_MS * 1.5, $maxTime, 
            "Max response time {$maxTime}ms exceeded acceptable limit");
    }

    /** @test */
    public function database_queries_are_optimized()
    {
        // Enable query logging
        \Illuminate\Support\Facades\DB::enableQueryLog();
        
        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['catalog:read']);

        Video::factory()->count(100)->published()->forTenant($this->tenant)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        
        $response->assertStatus(200);
        
        // Should not have excessive N+1 queries
        $this->assertLessThan(10, count($queries), 
            'Too many database queries executed: ' . count($queries));
    }

    /** @test */
    public function tenant_context_resolution_is_fast()
    {
        $executionTimes = [];

        for ($i = 0; $i < 20; $i++) {
            $start = microtime(true);
            
            // Test tenant resolution from header
            $request = \Illuminate\Http\Request::create('/', 'GET');
            $request->headers->set('X-Tenant', $this->tenant->id);
            
            $middleware = new \App\Http\Middleware\TenantContext();
            $middleware->handle($request, function ($req) {
                return new \Illuminate\Http\Response();
            });
            
            $executionTimes[] = (microtime(true) - $start) * 1000;
        }

        $averageTime = array_sum($executionTimes) / count($executionTimes);
        
        $this->assertLessThan(10, $averageTime, 
            "Tenant context resolution too slow: {$averageTime}ms");
    }

    /** @test */
    public function api_with_tenant_isolation_performs_well()
    {
        // Create multiple tenants with data
        $tenants = Tenant::factory()->count(10)->create();
        
        foreach ($tenants as $tenant) {
            Video::factory()->count(50)->published()->forTenant($tenant)->create();
        }

        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['catalog:read']);

        $this->startTiming();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response->assertStatus(200);
        $this->assertResponseTimeUnder(self::PERFORMANCE_THRESHOLD_MS);
        
        // Verify tenant isolation
        $data = $response->json('data');
        foreach ($data as $video) {
            $this->assertEquals($this->tenant->id, $video['tenant_id']);
        }
    }

    /** @test */
    public function memory_usage_stays_within_reasonable_limits()
    {
        $initialMemory = memory_get_usage();
        
        // Create large dataset
        Video::factory()->count(500)->published()->forTenant($this->tenant)->create();
        
        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog?per_page=100');

        $finalMemory = memory_get_usage();
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        $response->assertStatus(200);
        
        // Memory increase should be reasonable (less than 50MB)
        $this->assertLessThan(50, $memoryIncrease, 
            "Memory usage increased too much: {$memoryIncrease}MB");
    }

    /** @test */
    public function api_response_size_is_optimized()
    {
        Video::factory()->count(50)->published()->forTenant($this->tenant)->create();
        
        $user = User::factory()->forTenant($this->tenant)->create();
        $token = $user->createToken('perf-test', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $responseSize = strlen($response->getContent()) / 1024; // KB
        
        $response->assertStatus(200);
        
        // Response size should be reasonable (less than 500KB for 50 videos)
        $this->assertLessThan(500, $responseSize, 
            "API response too large: {$responseSize}KB");
    }

    public function performance_summary()
    {
        return [
            'catalog_api_threshold_ms' => self::PERFORMANCE_THRESHOLD_MS,
            'branding_api_threshold_ms' => self::PERFORMANCE_THRESHOLD_MS,
            'search_api_threshold_ms' => self::PERFORMANCE_THRESHOLD_MS,
            'authentication_threshold_ms' => self::PERFORMANCE_THRESHOLD_MS,
            'tenant_resolution_threshold_ms' => 10,
            'max_memory_usage_mb' => 50,
            'max_response_size_kb' => 500,
            'max_database_queries' => 10,
        ];
    }
}