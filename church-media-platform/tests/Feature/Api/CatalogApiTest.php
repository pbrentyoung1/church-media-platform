<?php

namespace Tests\Feature\Api;

use App\Models\Playlist;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class CatalogApiTest extends TenantTestCase
{
    use RefreshDatabase;

    /** @test */
    public function catalog_api_returns_tenant_videos_only()
    {
        $this->startTiming();

        // Create videos for current tenant
        $tenantVideos = Video::factory()
            ->count(5)
            ->published()
            ->forTenant($this->tenant)
            ->create();

        // Create videos for another tenant (should not appear in results)
        $otherTenant = Tenant::factory()->create(['subdomain' => 'otherchurch']);
        Video::factory()
            ->count(3)
            ->published()
            ->forTenant($otherTenant)
            ->create();

        // Create API token with catalog:read scope
        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'duration',
                    'video_url',
                    'thumbnail_url',
                    'category',
                    'published_at',
                ]
            ]
        ]);

        $data = $response->json('data');
        $this->assertCount(5, $data);

        // Verify all returned videos belong to current tenant
        foreach ($data as $video) {
            $this->assertEquals($this->tenant->id, $video['tenant_id']);
        }

        $this->assertTenantIsolated($response);
        $this->assertResponseTimeUnder(200);
    }

    /** @test */
    public function catalog_api_requires_valid_token()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response->assertStatus(401);
    }

    /** @test */
    public function catalog_api_requires_catalog_read_scope()
    {
        // Create token without catalog:read scope
        $token = $this->adminUser->createToken('limited-device', ['events:write']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response->assertStatus(403);
    }

    /** @test */
    public function catalog_api_returns_only_published_videos()
    {
        // Create published and draft videos
        Video::factory()->count(3)->published()->forTenant($this->tenant)->create();
        Video::factory()->count(2)->draft()->forTenant($this->tenant)->create();

        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data); // Only published videos

        foreach ($data as $video) {
            $this->assertEquals('published', $video['status']);
        }
    }

    /** @test */
    public function catalog_api_supports_pagination()
    {
        Video::factory()->count(25)->published()->forTenant($this->tenant)->create();

        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]
        ]);

        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    /** @test */
    public function catalog_api_supports_category_filtering()
    {
        Video::factory()->count(3)->published()->forTenant($this->tenant)->create(['category' => 'sermon']);
        Video::factory()->count(2)->published()->forTenant($this->tenant)->create(['category' => 'worship']);

        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog?category=sermon');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);

        foreach ($data as $video) {
            $this->assertEquals('sermon', $video['category']);
        }
    }

    /** @test */
    public function catalog_api_is_rate_limited()
    {
        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        // Make 61 requests (limit is 60 per minute)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token->plainTextToken,
                'Accept' => 'application/json',
                'X-Tenant' => $this->tenant->id,
            ])->get('/api/v1/catalog');

            if ($i < 60) {
                $this->assertLessThanOrEqual(200, $response->getStatusCode());
            }
        }

        // 61st request should be rate limited
        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function playlist_catalog_returns_tenant_playlists_only()
    {
        // Create playlists for current tenant
        $tenantPlaylists = Playlist::factory()
            ->count(3)
            ->public()
            ->forTenant($this->tenant)
            ->create();

        // Create playlist for another tenant
        $otherTenant = Tenant::factory()->create(['subdomain' => 'otherchurch']);
        Playlist::factory()->public()->forTenant($otherTenant)->create();

        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog/playlists');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(3, $data);

        foreach ($data as $playlist) {
            $this->assertEquals($this->tenant->id, $playlist['tenant_id']);
        }
    }

    /** @test */
    public function catalog_search_works_with_tenant_isolation()
    {
        $this->startTiming();

        // Create searchable videos for current tenant
        Video::factory()->published()->forTenant($this->tenant)->create([
            'title' => 'Sunday Morning Worship Service',
            'description' => 'Join us for worship and prayer',
        ]);

        Video::factory()->published()->forTenant($this->tenant)->create([
            'title' => 'Wednesday Bible Study',
            'description' => 'Study of Romans chapter 8',
        ]);

        // Create video with same search term for other tenant
        $otherTenant = Tenant::factory()->create();
        Video::factory()->published()->forTenant($otherTenant)->create([
            'title' => 'Sunday Morning Worship Service',
            'description' => 'Different church worship',
        ]);

        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/search?q=worship');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data); // Only current tenant's video

        $this->assertEquals($this->tenant->id, $data[0]['tenant_id']);
        $this->assertStringContainsString('worship', strtolower($data[0]['title']));

        $this->assertResponseTimeUnder(200);
    }

    /** @test */
    public function catalog_api_handles_empty_results_gracefully()
    {
        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response->assertStatus(200);
        $response->assertJson(['data' => []]);
    }

    /** @test */
    public function catalog_api_includes_proper_video_metadata()
    {
        $video = Video::factory()->published()->forTenant($this->tenant)->create([
            'title' => 'Test Sermon',
            'description' => 'A test sermon description',
            'duration' => 3600, // 1 hour
            'category' => 'sermon',
            'tags' => ['faith', 'hope', 'love'],
            'metadata' => [
                'resolution' => '1080p',
                'fps' => 30,
            ],
        ]);

        $token = $this->adminUser->createToken('roku-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/catalog');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $videoData = $data[0];

        $this->assertEquals('Test Sermon', $videoData['title']);
        $this->assertEquals('A test sermon description', $videoData['description']);
        $this->assertEquals(3600, $videoData['duration']);
        $this->assertEquals('sermon', $videoData['category']);
        $this->assertEquals(['faith', 'hope', 'love'], $videoData['tags']);
        $this->assertArrayHasKey('metadata', $videoData);
        $this->assertEquals('1080p', $videoData['metadata']['resolution']);
    }
}