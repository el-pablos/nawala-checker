<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
        ];
    }
}

