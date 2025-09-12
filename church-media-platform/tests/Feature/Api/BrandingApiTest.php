<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TenantTestCase;

class BrandingApiTest extends TenantTestCase
{
    use RefreshDatabase;

    /** @test */
    public function branding_api_returns_tenant_branding_configuration()
    {
        $this->startTiming();

        // Update tenant with specific branding
        $this->tenant->update([
            'branding_settings' => [
                'logo_url' => 'https://example.com/logo.png',
                'primary_color' => '#1f2937',
                'secondary_color' => '#6b7280',
                'background_color' => '#ffffff',
                'text_color' => '#000000',
            ],
        ]);

        $token = $this->adminUser->createToken('roku-device', ['branding:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'logo_url',
            'primary_color',
            'secondary_color',
            'roku_theme_json',
        ]);

        $data = $response->json();
        $this->assertEquals('https://example.com/logo.png', $data['logo_url']);
        $this->assertEquals('#1f2937', $data['primary_color']);
        $this->assertEquals('#6b7280', $data['secondary_color']);
        
        // Verify Roku-specific theme is generated
        $this->assertArrayHasKey('roku_theme_json', $data);
        $rokuTheme = $data['roku_theme_json'];
        $this->assertEquals('#1f2937', $rokuTheme['primaryColor']);
        $this->assertEquals('#6b7280', $rokuTheme['secondaryColor']);

        $this->assertResponseTimeUnder(200);
    }

    /** @test */
    public function branding_api_requires_branding_read_scope()
    {
        $token = $this->adminUser->createToken('limited-device', ['catalog:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(403);
    }

    /** @test */
    public function branding_api_returns_default_values_for_missing_settings()
    {
        // Create tenant with minimal branding
        $tenant = Tenant::factory()->create([
            'branding_settings' => null,
        ]);

        $user = User::factory()->forTenant($tenant)->create();
        $token = $user->createToken('roku-device', ['branding:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertNull($data['logo_url']);
        $this->assertEquals('#1f2937', $data['primary_color']); // Default value
        $this->assertEquals('#6b7280', $data['secondary_color']); // Default value
    }

    /** @test */
    public function branding_api_is_tenant_isolated()
    {
        // Create another tenant with different branding
        $otherTenant = Tenant::factory()->create([
            'subdomain' => 'otherchurch',
            'branding_settings' => [
                'logo_url' => 'https://other.com/logo.png',
                'primary_color' => '#ff0000',
                'secondary_color' => '#00ff00',
            ],
        ]);

        $token = $this->adminUser->createToken('roku-device', ['branding:read']);

        // Request branding for current tenant
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(200);
        
        $data = $response->json();
        // Should not contain other tenant's branding
        $this->assertNotEquals('https://other.com/logo.png', $data['logo_url']);
        $this->assertNotEquals('#ff0000', $data['primary_color']);
    }

    /** @test */
    public function branding_api_generates_proper_roku_theme()
    {
        $this->tenant->update([
            'branding_settings' => [
                'logo_url' => 'https://example.com/logo.png',
                'primary_color' => '#4f46e5',
                'secondary_color' => '#10b981',
                'background_color' => '#f8fafc',
                'text_color' => '#1f2937',
            ],
        ]);

        $token = $this->adminUser->createToken('roku-device', ['branding:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(200);
        
        $rokuTheme = $response->json('roku_theme_json');
        
        $this->assertEquals('#4f46e5', $rokuTheme['primaryColor']);
        $this->assertEquals('#10b981', $rokuTheme['secondaryColor']);
        $this->assertEquals('#f8fafc', $rokuTheme['backgroundColor']);
        $this->assertEquals('#1f2937', $rokuTheme['textColor']);
        $this->assertEquals('https://example.com/logo.png', $rokuTheme['logoUrl']);
    }

    /** @test */
    public function branding_api_handles_invalid_color_values()
    {
        $this->tenant->update([
            'branding_settings' => [
                'primary_color' => 'invalid-color',
                'secondary_color' => null,
            ],
        ]);

        $token = $this->adminUser->createToken('roku-device', ['branding:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(200);
        
        // Should fallback to defaults for invalid values
        $data = $response->json();
        $this->assertEquals('#1f2937', $data['primary_color']); // Default fallback
        $this->assertEquals('#6b7280', $data['secondary_color']); // Default fallback
    }

    /** @test */
    public function branding_api_caches_responses_for_performance()
    {
        $token = $this->adminUser->createToken('roku-device', ['branding:read']);

        // First request
        $start1 = microtime(true);
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');
        $time1 = (microtime(true) - $start1) * 1000;

        // Second request (should be faster due to caching)
        $start2 = microtime(true);
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');
        $time2 = (microtime(true) - $start2) * 1000;

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Both should return same data
        $this->assertEquals($response1->json(), $response2->json());
        
        // Both should be under performance threshold
        $this->assertLessThan(200, $time1);
        $this->assertLessThan(200, $time2);
    }

    /** @test */
    public function branding_api_includes_church_name_in_response()
    {
        $token = $this->adminUser->createToken('roku-device', ['branding:read']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/json',
            'X-Tenant' => $this->tenant->id,
        ])->get('/api/v1/branding');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'church_name' => $this->tenant->name,
        ]);
    }
}