<?php

namespace Database\Factories;

use App\Models\DeviceLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeviceLink>
 */
class DeviceLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'device_code_hash' => DeviceLink::generateDeviceCodeHash(),
            'user_code' => DeviceLink::generateUserCode(),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
            'approved_by' => null,
        ];
    }

    /**
     * Indicate that the device link is approved
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the device link is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subMinutes(30),
        ]);
    }

    /**
     * Set a specific tenant
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
