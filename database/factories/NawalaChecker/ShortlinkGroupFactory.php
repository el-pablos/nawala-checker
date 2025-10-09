<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\ShortlinkGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShortlinkGroupFactory extends Factory
{
    protected $model = ShortlinkGroup::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'rotation_threshold' => fake()->numberBetween(1, 5),
            'cooldown_seconds' => fake()->randomElement([60, 300, 600, 1800]),
            'min_confidence' => fake()->numberBetween(70, 90),
            'auto_rollback' => fake()->boolean(70),
            'created_by' => User::factory(),
        ];
    }
}

