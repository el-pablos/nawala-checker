<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationChannelFactory extends Factory
{
    protected $model = NotificationChannel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => 'telegram',
            'chat_id' => fake()->numerify('##########'),
            'user_id' => User::factory(),
            'group_id' => null,
            'is_active' => fake()->boolean(85),
            'notify_on_block' => fake()->boolean(90),
            'notify_on_recover' => fake()->boolean(85),
            'notify_on_rotation' => fake()->boolean(70),
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => Group::factory(),
        ]);
    }
}

