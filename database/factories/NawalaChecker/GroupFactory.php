<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'check_interval' => fake()->randomElement([60, 120, 300, 600, 1800, 3600]),
            'jitter_percent' => fake()->numberBetween(10, 20),
            'notifications_enabled' => fake()->boolean(80),
            'created_by' => User::factory(),
        ];
    }
}

