<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Playlist>
 */
class PlaylistFactory extends Factory
{
    protected $model = Playlist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->words(3, true);
        
        return [
            'tenant_id' => Tenant::factory(),
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'description' => $this->faker->sentence(),
            'thumbnail_url' => $this->faker->imageUrl(640, 360, 'abstract'),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'status' => $this->faker->randomElement(['published', 'draft', 'archived']),
        ];
    }

    /**
     * Create a published playlist
     */
    public function published(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'published',
            ];
        });
    }

    /**
     * Create a draft playlist
     */
    public function draft(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'draft',
            ];
        });
    }

    /**
     * Create a playlist for a specific tenant
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(function (array $attributes) use ($tenant) {
            return [
                'tenant_id' => $tenant->id,
            ];
        });
    }
}