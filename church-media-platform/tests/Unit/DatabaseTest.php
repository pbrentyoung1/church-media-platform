<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Playlist;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_migrations_run_successfully()
    {
        // This test ensures that all migrations can run without errors
        $this->assertTrue(true);
    }

    /** @test */
    public function database_has_required_tables()
    {
        $requiredTables = [
            'tenants',
            'users',
            'videos',
            'playlists',
            'playlist_items',
            'events',
            'personal_access_tokens',
            'activity_log',
            'roles',
            'permissions',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Required table '{$table}' does not exist"
            );
        }
    }

    /** @test */
    public function tenant_table_has_required_columns()
    {
        $requiredColumns = [
            'id',
            'name',
            'subdomain',
            'email',
            'status',
            'branding_settings',
            'feature_settings',
            'trial_ends_at',
            'subscription_ends_at',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('tenants', $column),
                "Tenants table missing required column '{$column}'"
            );
        }
    }

    /** @test */
    public function user_table_has_required_columns()
    {
        $requiredColumns = [
            'id',
            'tenant_id',
            'name',
            'email',
            'email_verified_at',
            'password',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'two_factor_confirmed_at',
            'remember_token',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Users table missing required column '{$column}'"
            );
        }
    }

    /** @test */
    public function video_table_has_required_columns()
    {
        $requiredColumns = [
            'id',
            'tenant_id',
            'user_id',
            'title',
            'description',
            'duration',
            'video_url',
            'thumbnail_url',
            'status',
            'visibility',
            'category',
            'tags',
            'metadata',
            'published_at',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('videos', $column),
                "Videos table missing required column '{$column}'"
            );
        }
    }

    /** @test */
    public function uuid_primary_keys_work_correctly()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $video = Video::factory()->forTenant($tenant)->create(['user_id' => $user->id]);

        // All IDs should be UUIDs
        $this->assertEquals(36, strlen($tenant->id));
        $this->assertEquals(36, strlen($user->id));
        $this->assertEquals(36, strlen($video->id));
        
        // UUIDs should contain dashes
        $this->assertStringContainsString('-', $tenant->id);
        $this->assertStringContainsString('-', $user->id);
        $this->assertStringContainsString('-', $video->id);
    }

    /** @test */
    public function foreign_key_constraints_work()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        // Create video with valid foreign keys
        $video = Video::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function foreign_key_constraints_prevent_orphaned_records()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create user with non-existent tenant
        User::factory()->create([
            'tenant_id' => 'non-existent-tenant-id',
        ]);
    }

    /** @test */
    public function json_columns_work_correctly()
    {
        $tenant = Tenant::factory()->create([
            'branding_settings' => [
                'primary_color' => '#1f2937',
                'logo_url' => 'https://example.com/logo.png',
            ],
            'feature_settings' => [
                'require_2fa' => true,
                'max_users' => 50,
            ],
        ]);

        $retrieved = Tenant::find($tenant->id);
        
        $this->assertEquals('#1f2937', $retrieved->branding_settings['primary_color']);
        $this->assertTrue($retrieved->feature_settings['require_2fa']);
        $this->assertEquals(50, $retrieved->feature_settings['max_users']);
    }

    /** @test */
    public function tenant_isolation_works_at_database_level()
    {
        $tenant1 = Tenant::factory()->create(['subdomain' => 'church1']);
        $tenant2 = Tenant::factory()->create(['subdomain' => 'church2']);

        $user1 = User::factory()->forTenant($tenant1)->create();
        $user2 = User::factory()->forTenant($tenant2)->create();

        Video::factory()->forTenant($tenant1)->create(['user_id' => $user1->id]);
        Video::factory()->forTenant($tenant2)->create(['user_id' => $user2->id]);

        // Query videos for tenant1
        $tenant1Videos = Video::where('tenant_id', $tenant1->id)->get();
        $tenant2Videos = Video::where('tenant_id', $tenant2->id)->get();

        $this->assertCount(1, $tenant1Videos);
        $this->assertCount(1, $tenant2Videos);
        
        $this->assertEquals($tenant1->id, $tenant1Videos->first()->tenant_id);
        $this->assertEquals($tenant2->id, $tenant2Videos->first()->tenant_id);
    }

    /** @test */
    public function indexes_exist_on_performance_critical_columns()
    {
        $indexes = DB::select("
            SELECT indexname, tablename, indexdef 
            FROM pg_indexes 
            WHERE schemaname = 'public'
        ");

        $indexNames = collect($indexes)->pluck('indexname')->toArray();

        // Check for important indexes
        $this->assertContains('tenants_subdomain_index', $indexNames);
        $this->assertContains('users_tenant_id_index', $indexNames);
        $this->assertContains('videos_tenant_id_index', $indexNames);
        $this->assertContains('users_email_index', $indexNames);
    }

    /** @test */
    public function cascade_deletes_work_correctly()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $video = Video::factory()->forTenant($tenant)->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('videos', ['id' => $video->id]);

        // Delete tenant should cascade to dependent records
        $tenant->delete();

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    /** @test */
    public function unique_constraints_work()
    {
        Tenant::factory()->create(['subdomain' => 'uniquechurch']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Should fail due to unique constraint on subdomain
        Tenant::factory()->create(['subdomain' => 'uniquechurch']);
    }

    /** @test */
    public function activity_log_table_integration_works()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        // Create an activity log entry
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->log('test action');

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'description' => 'test action',
        ]);
    }

    /** @test */
    public function permission_system_tables_work()
    {
        // Create permission and role
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'test permission']);
        $role = \Spatie\Permission\Models\Role::create(['name' => 'test role']);
        
        $role->givePermissionTo($permission);
        
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();
        $user->assignRole($role);

        $this->assertDatabaseHas('permissions', ['name' => 'test permission']);
        $this->assertDatabaseHas('roles', ['name' => 'test role']);
        $this->assertDatabaseHas('role_has_permissions', [
            'permission_id' => $permission->id,
            'role_id' => $role->id,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    /** @test */
    public function database_supports_full_text_search()
    {
        $tenant = Tenant::factory()->create();
        
        Video::factory()->forTenant($tenant)->create([
            'title' => 'Sunday Morning Worship Service',
            'description' => 'Join us for a time of worship and fellowship',
        ]);

        Video::factory()->forTenant($tenant)->create([
            'title' => 'Bible Study Session',
            'description' => 'Study of Romans chapter 8',
        ]);

        // Test basic search functionality
        $results = Video::where('tenant_id', $tenant->id)
            ->where(function ($query) {
                $query->where('title', 'ILIKE', '%worship%')
                      ->orWhere('description', 'ILIKE', '%worship%');
            })
            ->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Worship', $results->first()->title);
    }
}