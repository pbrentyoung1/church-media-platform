<?php

use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return response('<h1>🏠 Home Page</h1>
        <p>Welcome to the Church Media Platform!</p>
        <p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>
        <p><strong>Laravel Version:</strong> ' . app()->version() . '</p>
        <p><a href="/test">View Test Page</a></p>
        <p><a href="/login">Go to Login</a></p>
        <p><strong>Cache Status:</strong> Cleared ' . date('H:i:s') . '</p>
        <style>body{font-family:Arial;max-width:600px;margin:50px auto;padding:20px;}</style>', 200)
        ->header('Content-Type', 'text/html')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
});

// Simple test route - no middleware, no database, just plain text
Route::get('/test', function () {
    return response('<h1>✅ Laravel is Working!</h1>
        <p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>
        <p><strong>Laravel Version:</strong> ' . app()->version() . '</p>
        <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><a href="/">← Back to Homepage</a></p>
        <p><a href="/login">Go to Login</a></p>
        <style>body{font-family:Arial;max-width:600px;margin:50px auto;padding:20px;}</style>', 200)
        ->header('Content-Type', 'text/html');
});

// Real authentication routes
Route::middleware(['tenant.context'])->group(function () {
    Route::middleware(['guest'])->group(function () {
        Route::get('/login', function () {
            $tenant = \App\Services\TenantService::current();
            return response('<h1>🔐 Login to ' . ($tenant?->name ?? 'Church Media Platform') . '</h1>
                <form method="POST" action="/login">
                    ' . csrf_field() . '
                    <div style="margin:10px 0;">
                        <label>Email:</label><br>
                        <input type="email" name="email" required style="width:300px;padding:8px;" placeholder="admin@demo.church">
                    </div>
                    <div style="margin:10px 0;">
                        <label>Password:</label><br>
                        <input type="password" name="password" required style="width:300px;padding:8px;" placeholder="password123">
                    </div>
                    <div style="margin:15px 0;">
                        <button type="submit" style="background:#4f46e5;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;">Sign In</button>
                    </div>
                </form>
                <div style="background:#e0f2fe;padding:15px;margin:20px 0;border-radius:4px;">
                    <strong>Demo Credentials:</strong><br>
                    Email: admin@demo.church<br>
                    Password: password123
                </div>
                <p><a href="/">← Back to Home</a></p>
                <style>body{font-family:Arial;max-width:500px;margin:50px auto;padding:20px;}</style>', 200)
                ->header('Content-Type', 'text/html');
        })->name('login');

        Route::post('/login', function (\Illuminate\Http\Request $request) {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $tenant = \App\Services\TenantService::current();
            if (!$tenant) {
                return back()->withErrors(['email' => 'Tenant context required']);
            }

            if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
                $user = \Illuminate\Support\Facades\Auth::user();
                
                // Check user belongs to tenant
                if ($user->tenant_id !== $tenant->id) {
                    \Illuminate\Support\Facades\Auth::logout();
                    return back()->withErrors(['email' => 'Account not found for this organization']);
                }

                $request->session()->regenerate();
                return redirect('/dashboard')->with('success', 'Logged in successfully!');
            }

            return back()->withErrors(['email' => 'Invalid credentials']);
        });
    });

    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Logged out successfully!');
    })->name('logout')->middleware('auth');
});

// Protected dashboard route
Route::middleware(['tenant.context', 'auth', 'tenant.access'])->group(function () {
    Route::get('/dashboard', function () {
    // Cache the user to avoid multiple auth()->user() calls
    $user = auth()->user();
    
    $dbStats = '';
    try {
        $tenantCount = DB::table('tenants')->count();
        $userCount = DB::table('users')->count();
        $dbStats = "<div style='background:#e8f5e8;padding:15px;margin:15px 0;border-radius:4px;'>
            <strong>📊 Database Stats:</strong><br>
            • Tenants: {$tenantCount}<br>
            • Users: {$userCount}<br>
        </div>";
    } catch (Exception $e) {
        $dbStats = "<div style='background:#ffe6e6;padding:15px;margin:15px 0;border-radius:4px;'>
            <strong>❌ Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "
        </div>";
    }
    
    return response('<h1>📈 Dashboard</h1>
        <p>Welcome to the Church Media Platform Dashboard!</p>
        <p><strong>Current Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>Environment:</strong> ' . app()->environment() . '</p>
        ' . $dbStats . '
        
        <h2>🎛️ Management</h2>
        <ul>
            <li><a href="/admin/users">👥 Manage Users</a></li>
            <li><a href="/admin/settings">⚙️ Settings</a></li>
            <li><a href="/content/videos">🎥 Videos</a></li>
            <li><a href="/content/playlists">📚 Playlists</a></li>
            <li><a href="/content/events">📅 Events</a></li>
        </ul>
        
        <h2>🔗 Navigation</h2>
        <p><a href="/">← Back to Home</a></p>
        <p><a href="/test">🧪 Test Page</a></p>
        
        <h2>👤 Account</h2>
        <p><strong>Logged in as:</strong> ' . $user->name . ' (' . $user->email . ')</p>
        <form method="POST" action="/logout" style="display:inline;">
            ' . csrf_field() . '
            <button type="submit" style="background:#dc2626;color:white;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;">
                🚪 Logout
            </button>
        </form>
        
        <style>body{font-family:Arial;max-width:700px;margin:50px auto;padding:20px;} 
        ul{background:#f5f5f5;padding:20px;border-radius:8px;} 
        li{margin:10px 0;}</style>', 200)
        ->header('Content-Type', 'text/html');
    })->name('dashboard');
});

/*
// COMMENTED OUT: Tenant-specific web routes causing 500 errors
// These routes reference missing controllers and middleware
// Uncomment after creating the missing classes

Route::middleware(['tenant.context'])->group(function () {
    
    // Custom authentication routes
    Route::middleware(['guest'])->group(function () {
        Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'create'])
            ->name('login');
        Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'store']);
    });
    
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
    
    // Two-factor authentication routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/two-factor-setup', [App\Http\Controllers\Auth\TwoFactorController::class, 'setup'])
            ->name('two-factor.setup');
        Route::post('/user/two-factor-authentication', [App\Http\Controllers\Auth\TwoFactorController::class, 'enable'])
            ->name('two-factor.enable');
        Route::get('/user/two-factor-qr-code', [App\Http\Controllers\Auth\TwoFactorController::class, 'qrCode'])
            ->name('two-factor.qr-code');
        Route::post('/user/confirmed-two-factor-authentication', [App\Http\Controllers\Auth\TwoFactorController::class, 'confirm'])
            ->name('two-factor.confirm');
        Route::get('/user/two-factor-recovery-codes', [App\Http\Controllers\Auth\TwoFactorController::class, 'recoveryCodes'])
            ->name('two-factor.recovery-codes');
        Route::post('/user/two-factor-recovery-codes', [App\Http\Controllers\Auth\TwoFactorController::class, 'newRecoveryCodes'])
            ->name('two-factor.new-recovery-codes');
        Route::delete('/user/two-factor-authentication', [App\Http\Controllers\Auth\TwoFactorController::class, 'disable'])
            ->name('two-factor.disable');
    });
    
    // Two-factor challenge (for partially authenticated users)
    Route::get('/two-factor-challenge', [App\Http\Controllers\Auth\TwoFactorController::class, 'challenge'])
        ->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [App\Http\Controllers\Auth\TwoFactorController::class, 'verify'])
        ->name('two-factor.verify');
    
    // Protected dashboard routes
    Route::middleware(['auth', 'tenant.access', 'verified'])->group(function () {
        
        Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
            ->name('dashboard');
        
        // Admin routes requiring 2FA
        Route::middleware(['requires.2fa:admin,super-admin'])->group(function () {
            
            Route::get('/admin/users', function () {
                return view('admin.users');
            })->name('admin.users');
            
            Route::get('/admin/settings', function () {
                return view('admin.settings');
            })->name('admin.settings');
            
            Route::get('/admin/analytics', function () {
                return view('admin.analytics');
            })->name('admin.analytics');
            
        });
        
        // Content management routes
        Route::prefix('content')->name('content.')->group(function () {
            
            Route::get('/videos', function () {
                return view('content.videos');
            })->name('videos');
            
            Route::get('/playlists', function () {
                return view('content.playlists');
            })->name('playlists');
            
            Route::get('/events', function () {
                return view('content.events');
            })->name('events');
            
        });
        
    });
    
});
*/
