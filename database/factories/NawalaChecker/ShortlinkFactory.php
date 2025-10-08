<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShortlinkFactory extends Factory
{
    protected $model = Shortlink::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'group_id' => null,
            'current_target_id' => null,
            'original_target_id' => null,
            'is_active' => fake()->boolean(90),
            'last_rotated_at' => fake()->optional(0.3)->dateTimeBetween('-1 month', 'now'),
            'rotation_count' => fake()->numberBetween(0, 10),
            'created_by' => User::factory(),
            'metadata' => null,
        ];
    }

    public function withGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => ShortlinkGroup::factory(),
        ]);
    }
}

