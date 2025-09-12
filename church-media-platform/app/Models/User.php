<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, 
        Notifiable, 
        HasUuids, 
        HasApiTokens, 
        TwoFactorAuthenticatable,
        HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'two_factor_enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the user
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get device links approved by this user
     */
    public function approvedDeviceLinks(): HasMany
    {
        return $this->hasMany(DeviceLink::class, 'approved_by');
    }

    /**
     * Determine if two-factor authentication is enabled
     */
    public function getTwoFactorEnabledAttribute(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Get the user's role names for this tenant
     */
    public function getTenantRoles(): array
    {
        // For now, return all user roles since tenant-specific roles
        // would require custom pivot table with tenant_id column
        return $this->roles()
            ->pluck('name')
            ->toArray();
    }

    /**
     * Check if user has permission within their tenant
     */
    public function hasTenantPermission(string $permission): bool
    {
        // Avoid circular reference - use static tenant context instead of auth()->user()
        $currentTenant = app()->bound('tenant') ? app('tenant') : null;
        return $this->hasPermissionTo($permission) && 
               $currentTenant && $this->tenant_id === $currentTenant->id;
    }

    /**
     * Scope a query to only include users from the current tenant
     */
    public function scopeTenant($query)
    {
        // Avoid circular reference - use static tenant context instead of auth()->user()
        $currentTenant = app()->bound('tenant') ? app('tenant') : null;
        if ($currentTenant) {
            return $query->where('tenant_id', $currentTenant->id);
        }
        
        return $query;
    }

    /**
     * Boot the model - global scopes removed to prevent infinite recursion
     */
    protected static function booted(): void
    {
        // Note: Tenant isolation handled by middleware instead of global scopes
        // to prevent infinite recursion with auth()->user() calls
        
        static::creating(function ($user) {
            // Only set tenant_id if not already set and we have a tenant in context
            if (!$user->tenant_id && app()->bound('tenant')) {
                $tenant = app('tenant');
                if ($tenant) {
                    $user->tenant_id = $tenant->id;
                }
            }
        });
    }
}
