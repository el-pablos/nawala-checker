<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Target;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TargetFactory extends Factory
{
    protected $model = Target::class;

    public function definition(): array
    {
        $domains = [
            'reddit.com',
            'vimeo.com',
            'imgur.com',
            'tumblr.com',
            'netflix.com',
            'example.com',
            'test.com',
        ];

        $type = fake()->randomElement(['domain', 'url']);
        $domain = fake()->randomElement($domains);
        
        return [
            'domain_or_url' => $type === 'domain' ? $domain : 'https://' . $domain . '/' . fake()->slug(),
            'type' => $type,
            'group_id' => null,
            'owner_id' => User::factory(),
            'enabled' => fake()->boolean(85),
            'check_interval' => fake()->optional(0.3)->randomElement([60, 120, 300, 600]),
            'current_status' => fake()->randomElement(['UNKNOWN', 'OK', 'DNS_FILTERED', 'HTTP_BLOCKPAGE', 'TIMEOUT']),
            'last_checked_at' => fake()->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'last_status_change_at' => fake()->optional(0.5)->dateTimeBetween('-1 month', 'now'),
            'consecutive_failures' => fake()->numberBetween(0, 5),
            'notes' => fake()->optional(0.3)->sentence(),
            'metadata' => null,
        ];
    }

    public function withGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => Group::factory(),
        ]);
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}

