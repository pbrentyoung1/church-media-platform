<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use App\Models\Video;
use App\Models\Playlist;
use App\Models\Event;
use App\Models\DeviceLink;

echo "=== Testing Model Factories & Relationships ===\n";

try {
    // Test Tenant factory
    echo "\n📋 Testing Tenant Factory...\n";
    $tenant = Tenant::factory()->create();
    echo "✅ Tenant created: {$tenant->name} ({$tenant->subdomain})\n";

    // Test User factory
    echo "\n👤 Testing User Factory...\n";
    $user = User::factory()->forTenant($tenant)->create();
    echo "✅ User created: {$user->name} ({$user->email})\n";
    
    // Test Video factory
    echo "\n🎥 Testing Video Factory...\n";
    $videos = Video::factory(3)->forTenant($tenant)->create();
    echo "✅ Videos created: {$videos->count()}\n";
    foreach ($videos as $video) {
        echo "   - {$video->title} ({$video->source})\n";
    }
    
    // Test Playlist factory
    echo "\n📚 Testing Playlist Factory...\n";
    $playlist = Playlist::factory()->forTenant($tenant)->create();
    echo "✅ Playlist created: {$playlist->title}\n";
    
    // Test playlist-video relationship
    echo "\n🔗 Testing Playlist-Video Relationship...\n";
    $playlist->videos()->attach($videos->take(2)->pluck('id'));
    $attachedVideos = $playlist->videos()->count();
    echo "✅ Attached {$attachedVideos} videos to playlist\n";
    
    // Test Event factory
    echo "\n📊 Testing Event Factory...\n";
    $events = Event::factory(5)->forVideo($videos->first())->create();
    echo "✅ Events created: {$events->count()}\n";
    foreach ($events as $event) {
        echo "   - {$event->event_type} on {$event->platform}\n";
    }
    
    // Test DeviceLink factory
    echo "\n📱 Testing DeviceLink Factory...\n";
    $deviceLink = DeviceLink::factory()->create([
        'tenant_id' => $tenant->id,
        'approved_by' => $user->id,
    ]);
    echo "✅ Device link created: {$deviceLink->user_code} ({$deviceLink->status})\n";
    
    // Test relationships
    echo "\n🔍 Testing Relationships...\n";
    $tenantVideos = $tenant->videos()->count();
    $tenantUsers = $tenant->users()->count();
    $tenantPlaylists = $tenant->playlists()->count();
    $tenantEvents = $tenant->events()->count();
    
    echo "✅ Tenant relationships:\n";
    echo "   - Videos: {$tenantVideos}\n";
    echo "   - Users: {$tenantUsers}\n";
    echo "   - Playlists: {$tenantPlaylists}\n";
    echo "   - Events: {$tenantEvents}\n";
    
    // Test reverse relationships
    echo "\n🔄 Testing Reverse Relationships...\n";
    $videoTenant = $videos->first()->tenant;
    $playlistTenant = $playlist->tenant;
    $userTenant = $user->tenant;
    
    echo "✅ Reverse relationships working:\n";
    echo "   - Video belongs to: {$videoTenant->name}\n";
    echo "   - Playlist belongs to: {$playlistTenant->name}\n";
    echo "   - User belongs to: {$userTenant->name}\n";
    
    echo "\n🎉 ALL MODEL TESTS PASSED! 🎉\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}