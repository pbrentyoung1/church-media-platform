<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DeviceLink extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'device_code_hash',
        'user_code',
        'status',
        'expires_at',
        'approved_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the device link
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who approved the device link
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate a random 6-digit user code
     */
    public static function generateUserCode(): string
    {
        do {
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('user_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a device code hash
     */
    public static function generateDeviceCodeHash(): string
    {
        return hash('sha256', Str::random(32) . microtime());
    }

    /**
     * Check if the device link is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the device link is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Check if the device link is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Approve the device link
     */
    public function approve(User $user): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);

        return true;
    }

    /**
     * Expire the device link
     */
    public function expire(): bool
    {
        $this->update(['status' => 'expired']);
        return true;
    }

    /**
     * Scope to pending device links
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to approved device links
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to expired device links
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                    ->orWhere('expires_at', '<=', now());
    }

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::creating(function ($deviceLink) {
            if (!$deviceLink->tenant_id && app()->bound('tenant')) {
                $tenant = app('tenant');
                if ($tenant) {
                    $deviceLink->tenant_id = $tenant->id;
                }
            }

            if (!$deviceLink->user_code) {
                $deviceLink->user_code = static::generateUserCode();
            }

            if (!$deviceLink->device_code_hash) {
                $deviceLink->device_code_hash = static::generateDeviceCodeHash();
            }

            if (!$deviceLink->expires_at) {
                $deviceLink->expires_at = now()->addMinutes(15); // 15 minute expiry
            }
        });
    }
}