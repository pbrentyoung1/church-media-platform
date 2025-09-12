<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'manage-church-settings',
            'upload-videos',
            'manage-playlists',
            'view-analytics',
            'manage-users',
            'publish-roku-channel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create a sample tenant
        $tenant = Tenant::firstOrCreate(
            ['subdomain' => 'demo-church'],
            [
                'name' => 'Demo Church',
                'email' => 'admin@demo-church.com',
                'status' => 'active',
                'branding_settings' => [
                    'primary_color' => '#1f2937',
                    'secondary_color' => '#6b7280',
                    'logo_url' => null,
                ],
                'feature_settings' => [
                    'roku_enabled' => true,
                    'analytics_enabled' => true,
                    'two_factor_required' => true,
                ],
            ]
        );

        // Create roles for this tenant
        $adminRole = Role::firstOrCreate(
            ['name' => 'church-admin', 'guard_name' => 'web'],
        );
        $adminRole->givePermissionTo($permissions);

        $editorRole = Role::firstOrCreate(
            ['name' => 'content-manager', 'guard_name' => 'web'],
        );
        $editorRole->givePermissionTo([
            'upload-videos',
            'manage-playlists',
            'view-analytics',
        ]);

        // Create admin user
        $adminUser = User::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => 'admin@demo-church.com'
            ],
            [
                'name' => 'Church Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $adminUser->assignRole($adminRole);

        // Create content manager user
        $editorUser = User::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => 'editor@demo-church.com'
            ],
            [
                'name' => 'Content Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $editorUser->assignRole($editorRole);

        $this->command->info('Demo tenant created with admin and editor users');
        $this->command->info('Login credentials:');
        $this->command->info('Admin: admin@demo-church.com / password');
        $this->command->info('Editor: editor@demo-church.com / password');
    }
}
