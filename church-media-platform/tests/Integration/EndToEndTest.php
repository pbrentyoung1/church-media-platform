<?php

namespace Tests\Integration;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TenantTestCase;

class EndToEndTest extends TenantTestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_tenant_setup_and_authentication_flow()
    {
        // Step 1: Create tenant
        $tenant = Tenant::factory()->create([
            'name' => 'Integration Test Church',
            'subdomain' => 'integrationtest',
            'status' => 'active',
            'branding_settings' => [
                'primary_color' => '#4f46e5',
                'logo_url' => 'https://example.com/logo.png',
            ],
        ]);

        // Step 2: Create admin user
        $admin = User::factory()->forTenant($tenant)->create([
            'email' => 'admin@integrationtest.com',
            'password' => Hash::make('SecurePassword123!'),
        ]);
        $admin->assignRole('admin');

        // Step 3: Test login flow
        $response = $this->withHeaders([
            'Host' => 'integrationtest.forworship.com',
            'X-Tenant' => $tenant->id,
        ])->post('/login', [
            'email' => 'admin@integrationtest.com',
            'password' => 'SecurePassword123!',
        ]);

        $response->assertStatus(302);
        
        // Should redirect to 2FA setup (admin requires 2FA)
        $response->assertRedirect('/two-factor-setup');

        // Step 4: Set up 2FA
        $this->actingAs($admin);
        
        $setupResponse = $this->withHeaders([
            'Host' => 'integrationtest.forworship.com',
            'X-Tenant' => $tenant->id,
        ])->post('/user/two-factor-authentication');

        $setupResponse->assertStatus(302);

        // Step 5: Get QR code
        $admin->refresh();
        $qrResponse = $this->withHeaders([
            'Host' => 'integrationtest.forworship.com',
            'X-Tenant' => $tenant->id,
        ])->get('/user/two-factor-qr-code');

        $qrResponse->assertStatus(200);
        $qrData = $qrResponse->json();
        $this->assertArrayHasKey('svg', $qrData);

        // Step 6: Confirm 2FA
        $google2fa = new Google2FA();
        $validCode = $google2fa->getCurrentOtp($admin->two_factor_secret);

        $confirmResponse = $this->withHeaders([
            'Host' => 'integrationtest.forworship.com',
            'X-Tenant' => $tenant->id,
        ])->post('/user/confirmed-two-factor-authentication', [
            'code' => $validCode,
        ]);

        $confirmResponse->assertStatus(302)
            ->assertRedirect('/dashboard');

        // Step 7: Access dashboard
        $dashboardResponse = $this->withHeaders([
            'Host' => 'integrationtest.forworship.com',
            'X-Tenant' => $tenant->id,
        ])->get('/dashboard');

        $dashboardResponse->assertStatus(200);
        
        $this->assertTrue(true); // End-to-end flow completed successfully
    }

    /** @test */
    public function roku_device_integration_flow()
    {
        // Step 1: Create content
        $videos = Video::factory()->count(10)->published()->forTenant($this->tenant)->create();

        // Step 2: Create API token for Roku device
        $token = $this->adminUser->createToken('roku-channel', [
            'catalog:read',
            'branding:read',
            'live:read',
        ]);

        // Step 3: Test catalog API
        $catalogResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $catalogResponse->assertStatus(200);
        
        $catalogData = $catalogResponse->json();
        $this->assertCount(10, $catalogData['data']);
        $this->assertEquals($this->tenant->id, $catalogData['data'][0]['tenant_id']);

        // Step 4: Test branding API
        $brandingResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $brandingResponse->assertStatus(200);
        
        $brandingData = $brandingResponse->json();
        $this->assertArrayHasKey('roku_theme_json', $brandingData);
        $this->assertEquals($this->tenant->name, $brandingData['church_name']);

        // Step 5: Test search API
        $searchResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/search?q=' . $videos->first()->title);

        $searchResponse->assertStatus(200);
        
        $searchResults = $searchResponse->json();
        $this->assertGreaterThan(0, count($searchResults['data']));
    }

    /** @test */
    public function multi_tenant_isolation_verification()
    {
        // Create second tenant with data
        $tenant2 = Tenant::factory()->create([
            'name' => 'Second Church',
            'subdomain' => 'secondchurch',
        ]);

        $user2 = User::factory()->forTenant($tenant2)->create();
        $videos2 = Video::factory()->count(5)->published()->forTenant($tenant2)->create();

        // Create tokens for both tenants
        $token1 = $this->adminUser->createToken('tenant1-token', ['catalog:read']);
        $token2 = $user2->createToken('tenant2-token', ['catalog:read']);

        // Test tenant 1 isolation
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response1->assertStatus(200);
        $data1 = $response1->json('data');

        // Test tenant 2 isolation
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $tenant2->id,
        ])->get('/api/v1/catalog');

        $response2->assertStatus(200);
        $data2 = $response2->json('data');

        // Verify isolation - no cross-tenant data
        foreach ($data1 as $item) {
            $this->assertEquals($this->tenant->id, $item['tenant_id']);
        }

        foreach ($data2 as $item) {
            $this->assertEquals($tenant2->id, $item['tenant_id']);
        }

        // Test cross-tenant access denial
        $crossResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $tenant2->id, // Wrong tenant
        ])->get('/api/v1/catalog');

        $crossResponse->assertStatus(403); // Should be denied
    }

    /** @test */
    public function performance_with_realistic_data_load()
    {
        // Create realistic data set
        $users = User::factory()->count(25)->forTenant($this->tenant)->create();
        $videos = Video::factory()->count(500)->published()->forTenant($this->tenant)->create();

        // Simulate multiple concurrent API requests
        $times = [];
        
        for ($i = 0; $i < 5; $i++) {
            $user = $users->random();
            $token = $user->createToken('perf-test-' . $i, ['catalog:read']);
            
            $start = microtime(true);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token->plainTextToken,
                'Accept' => 'application/json',
                'X-Tenant' => $this->tenant->id,
            ])->get('/api/v1/catalog?per_page=50&page=' . ($i + 1));
            
            $times[] = (microtime(true) - $start) * 1000;
            
            $response->assertStatus(200);
            
            // Verify pagination works
            $data = $response->json();
            $this->assertLessThanOrEqual(50, count($data['data']));
        }

        $averageTime = array_sum($times) / count($times);
        $this->assertLessThan(200, $averageTime, 
            "Average API response time too slow: {$averageTime}ms");
    }

    /** @test */
    public function complete_video_management_workflow()
    {
        $editor = User::factory()->forTenant($this->tenant)->create();
        $editor->assignRole('editor');

        // Step 1: Create video
        $video = Video::factory()->forTenant($this->tenant)->create([
            'user_id' => $editor->id,
            'status' => 'draft',
            'title' => 'Sunday Service - Integration Test',
        ]);

        // Step 2: Publish video (simulate admin approval)
        $video->update([
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
        ]);

        // Step 3: Verify video appears in catalog
        $token = $editor->createToken('editor-token', ['catalog:read']);
        
        $catalogResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $catalogResponse->assertStatus(200);
        
        $catalogVideos = $catalogResponse->json('data');
        $publishedVideo = collect($catalogVideos)->firstWhere('id', $video->id);
        
        $this->assertNotNull($publishedVideo);
        $this->assertEquals('published', $publishedVideo['status']);
        $this->assertEquals('Sunday Service - Integration Test', $publishedVideo['title']);

        // Step 4: Test search functionality
        $searchResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/search?q=Sunday');

        $searchResponse->assertStatus(200);
        
        $searchResults = $searchResponse->json('data');
        $foundVideo = collect($searchResults)->firstWhere('id', $video->id);
        
        $this->assertNotNull($foundVideo);
    }

    /** @test */
    public function security_and_authorization_integration()
    {
        // Create users with different roles
        $viewer = User::factory()->forTenant($this->tenant)->create();
        $viewer->assignRole('viewer');
        
        $editor = User::factory()->forTenant($this->tenant)->create();
        $editor->assignRole('editor');

        // Test limited scope tokens
        $viewerToken = $viewer->createToken('viewer-token', ['catalog:read']);
        $editorToken = $editor->createToken('editor-token', ['catalog:read', 'events:write']);

        // Viewer should be able to read catalog
        $viewerResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $viewerToken->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $viewerResponse->assertStatus(200);

        // Viewer should NOT be able to create events
        $viewerEventResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $viewerToken->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->post('/api/v1/events', [
            'title' => 'Test Event',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        $viewerEventResponse->assertStatus(403); // Forbidden

        // Editor should be able to create events
        $editorEventResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $editorToken->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->post('/api/v1/events', [
            'title' => 'Editor Event',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
        ]);

        $editorEventResponse->assertStatus(200); // Success
    }
}