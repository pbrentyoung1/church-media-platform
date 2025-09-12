<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'video_id' => null, // Will be set by forVideo() method if needed
            'platform' => $this->faker->randomElement(['roku', 'appletv', 'firetv', 'web']),
            'event_type' => $this->faker->randomElement(['play', 'pause', 'complete', 'seek', 'error']),
            'seconds' => $this->faker->optional(0.8)->numberBetween(0, 7200), // Position in video
            'metadata' => [
                'device_model' => $this->faker->randomElement(['Roku Ultra', 'Apple TV 4K', 'Fire TV Stick']),
                'app_version' => $this->faker->randomElement(['1.0.0', '1.0.1', '1.1.0']),
                'connection_type' => $this->faker->randomElement(['wifi', 'ethernet']),
            ],
            'occurred_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Create a play event
     */
    public function play(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'play',
                'seconds' => 0,
            ];
        });
    }

    /**
     * Create a completion event
     */
    public function complete(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'complete',
                'seconds' => $this->faker->numberBetween(300, 7200), // Completed video duration
            ];
        });
    }

    /**
     * Create an event for a specific tenant
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(function (array $attributes) use ($tenant) {
            return [
                'tenant_id' => $tenant->id,
            ];
        });
    }

    /**
     * Create an event for a specific video
     */
    public function forVideo($video): static
    {
        return $this->state(function (array $attributes) use ($video) {
            return [
                'video_id' => $video->id,
                'tenant_id' => $video->tenant_id,
            ];
        });
    }
}