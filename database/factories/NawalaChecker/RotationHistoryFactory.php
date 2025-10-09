<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\RotationHistory;
use App\Models\NawalaChecker\Shortlink;
use App\Models\NawalaChecker\ShortlinkTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RotationHistoryFactory extends Factory
{
    protected $model = RotationHistory::class;

    public function definition(): array
    {
        return [
            'shortlink_id' => Shortlink::factory(),
            'from_target_id' => ShortlinkTarget::factory(),
            'to_target_id' => ShortlinkTarget::factory(),
            'reason' => fake()->randomElement(['manual', 'auto', 'threshold', 'rollback']),
            'triggered_by' => User::factory(),
            'rotated_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'metadata' => null,
        ];
    }
}

