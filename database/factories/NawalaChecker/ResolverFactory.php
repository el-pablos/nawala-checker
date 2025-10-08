<?php

namespace Database\Factories\NawalaChecker;

use App\Models\NawalaChecker\Resolver;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResolverFactory extends Factory
{
    protected $model = Resolver::class;

    public function definition(): array
    {
        $resolvers = [
            ['name' => 'Nawala DNS 1', 'address' => '180.131.144.144', 'type' => 'dns'],
            ['name' => 'Nawala DNS 2', 'address' => '180.131.145.145', 'type' => 'dns'],
            ['name' => 'Google DNS 1', 'address' => '8.8.8.8', 'type' => 'dns'],
            ['name' => 'Google DNS 2', 'address' => '8.8.4.4', 'type' => 'dns'],
            ['name' => 'Cloudflare DNS 1', 'address' => '1.1.1.1', 'type' => 'dns'],
            ['name' => 'Cloudflare DNS 2', 'address' => '1.0.0.1', 'type' => 'dns'],
            ['name' => 'Quad9 DNS', 'address' => '9.9.9.9', 'type' => 'dns'],
            ['name' => 'Cloudflare DoH', 'address' => 'https://cloudflare-dns.com/dns-query', 'type' => 'doh'],
            ['name' => 'Google DoH', 'address' => 'https://dns.google/dns-query', 'type' => 'doh'],
        ];

        $resolver = fake()->randomElement($resolvers);
        
        return [
            'name' => $resolver['name'],
            'type' => $resolver['type'],
            'address' => $resolver['address'],
            'port' => $resolver['type'] === 'dns' ? 53 : null,
            'is_active' => fake()->boolean(90),
            'priority' => fake()->numberBetween(1, 100),
            'weight' => fake()->numberBetween(50, 100),
            'metadata' => null,
        ];
    }
}

