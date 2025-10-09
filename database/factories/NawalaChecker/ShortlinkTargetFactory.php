<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShortlinkTargetFactory extends Factory
{
    protected $model = ShortlinkTarget::class;

    public function definition(): array
    {
        return [
            'shortlink_id' => Shortlink::factory(),
            'url' => fake()->url(),
            'priority' => fake()->numberBetween(1, 100),
            'weight' => fake()->numberBetween(50, 100),
            'is_active' => fake()->boolean(90),
            'current_status' => fake()->randomElement(['OK', 'DNS_FILTERED', 'TIMEOUT', 'UNKNOWN']),
            'last_checked_at' => fake()->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'current_status' => 'OK',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_status' => 'DNS_FILTERED',
        ]);
    }
}

