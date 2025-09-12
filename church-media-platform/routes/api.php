<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
// COMMENTED OUT: User endpoint with missing middleware
// Route::middleware(['auth:sanctum', 'tenant.context', 'tenant.access'])->get('/user', function (Request $request) {
//     return $request->user();
// });
*/

/*
// COMMENTED OUT: API v1 routes with missing middleware causing 500 errors
// These routes reference missing tenant.context, tenant.access, and requires.2fa middleware
// Uncomment after creating the missing middleware classes

Route::prefix('v1')->middleware(['throttle:60,1', 'tenant.context'])->group(function () {
    
    // Public routes (tenant context required but no auth)
    Route::get('/catalog', function () {
        // Video catalog endpoint - implement controller
        return response()->json(['message' => 'Catalog endpoint']);
    })->middleware(['ability:catalog:read']);
    
    Route::get('/branding', function () {
        // Church branding endpoint - implement controller  
        return response()->json(['message' => 'Branding endpoint']);
    })->middleware(['ability:branding:read']);
    
    Route::get('/search', function () {
        // Search functionality - implement controller
        return response()->json(['message' => 'Search endpoint']);
    });
    
    Route::get('/live', function () {
        // Live stream data - implement controller
        return response()->json(['message' => 'Live endpoint']);
    })->middleware(['ability:live:read']);
    
    // Protected routes requiring sanctum auth, tenant access, and specific scopes
    Route::middleware(['auth:sanctum', 'tenant.access'])->group(function () {
        
        // Events - requires events:write scope
        Route::post('/events', function () {
            // Event creation - implement controller
            return response()->json(['message' => 'Event creation endpoint']);
        })->middleware(['ability:events:write']);
        
        // Admin routes requiring 2FA
        Route::middleware(['requires.2fa:admin,super-admin'])->group(function () {
            
            Route::put('/tenant/settings', function () {
                // Tenant settings update - admin only
                return response()->json(['message' => 'Tenant settings endpoint']);
            });
            
            Route::delete('/videos/{video}', function () {
                // Video deletion - admin only
                return response()->json(['message' => 'Video deletion endpoint']);
            });
            
        });
        
    });
    
});
*/