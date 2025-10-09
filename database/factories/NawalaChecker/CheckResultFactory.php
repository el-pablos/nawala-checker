<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\CheckResult;
use App\Models\NawalaChecker\Resolver;
use App\Models\NawalaChecker\Target;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckResultFactory extends Factory
{
    protected $model = CheckResult::class;

    public function definition(): array
    {
        $statuses = ['OK', 'DNS_FILTERED', 'HTTP_BLOCKPAGE', 'HTTPS_SNI_BLOCK', 'TIMEOUT', 'RST', 'INCONCLUSIVE'];
        $status = fake()->randomElement($statuses);
        
        return [
            'target_id' => Target::factory(),
            'resolver_id' => Resolver::factory(),
            'vantage_node_id' => null,
            'status' => $status,
            'response_time_ms' => fake()->numberBetween(10, 5000),
            'resolved_ip' => $status === 'DNS_FILTERED' ? '103.10.66.1' : fake()->ipv4(),
            'http_status_code' => $status === 'OK' ? 200 : ($status === 'HTTP_BLOCKPAGE' ? 403 : null),
            'error_message' => $status === 'TIMEOUT' ? 'Connection timeout' : null,
            'raw_response' => null,
            'confidence' => fake()->numberBetween(60, 100),
            'checked_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'DNS_FILTERED',
            'resolved_ip' => '103.10.66.1',
        ]);
    }

    public function accessible(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'OK',
            'http_status_code' => 200,
        ]);
    }

    public function timeout(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'TIMEOUT',
            'error_message' => 'Connection timeout',
        ]);
    }
}

