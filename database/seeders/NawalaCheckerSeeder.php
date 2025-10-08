<?php

namespace Database\Seeders;

use App\Models\NawalaChecker\Group;
use App\Models\NawalaChecker\Resolver;
use App\Models\NawalaChecker\ShortlinkGroup;
use App\Models\NawalaChecker\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class NawalaCheckerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default user if not exists
        $user = User::firstOrCreate(
            ['email' => 'admin@nawala-checker.test'],
            [
                'name' => 'Admin Nawala',
                'password' => bcrypt('password'),
            ]
        );

        // Create default tags
        $tags = [
            ['name' => 'Social Media', 'slug' => 'social-media', 'color' => '#3B82F6'],
            ['name' => 'Streaming', 'slug' => 'streaming', 'color' => '#EF4444'],
            ['name' => 'News', 'slug' => 'news', 'color' => '#10B981'],
            ['name' => 'E-Commerce', 'slug' => 'e-commerce', 'color' => '#F59E0B'],
            ['name' => 'Critical', 'slug' => 'critical', 'color' => '#DC2626'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }

        // Create default resolvers
        $resolvers = [
            [
                'name' => 'Nawala DNS Primary',
                'type' => 'dns',
                'address' => '180.131.144.144',
                'port' => 53,
                'is_active' => true,
                'priority' => 10,
                'weight' => 100,
            ],
            [
                'name' => 'Nawala DNS Secondary',
                'type' => 'dns',
                'address' => '180.131.145.145',
                'port' => 53,
                'is_active' => true,
                'priority' => 10,
                'weight' => 100,
            ],
            [
                'name' => 'Google DNS Primary',
                'type' => 'dns',
                'address' => '8.8.8.8',
                'port' => 53,
                'is_active' => true,
                'priority' => 20,
                'weight' => 100,
            ],
            [
                'name' => 'Google DNS Secondary',
                'type' => 'dns',
                'address' => '8.8.4.4',
                'port' => 53,
                'is_active' => true,
                'priority' => 20,
                'weight' => 100,
            ],
            [
                'name' => 'Cloudflare DNS Primary',
                'type' => 'dns',
                'address' => '1.1.1.1',
                'port' => 53,
                'is_active' => true,
                'priority' => 30,
                'weight' => 100,
            ],
            [
                'name' => 'Cloudflare DNS Secondary',
                'type' => 'dns',
                'address' => '1.0.0.1',
                'port' => 53,
                'is_active' => true,
                'priority' => 30,
                'weight' => 100,
            ],
            [
                'name' => 'Quad9 DNS',
                'type' => 'dns',
                'address' => '9.9.9.9',
                'port' => 53,
                'is_active' => true,
                'priority' => 40,
                'weight' => 100,
            ],
            [
                'name' => 'Cloudflare DoH',
                'type' => 'doh',
                'address' => 'https://cloudflare-dns.com/dns-query',
                'port' => null,
                'is_active' => true,
                'priority' => 50,
                'weight' => 100,
            ],
            [
                'name' => 'Google DoH',
                'type' => 'doh',
                'address' => 'https://dns.google/dns-query',
                'port' => null,
                'is_active' => true,
                'priority' => 50,
                'weight' => 100,
            ],
        ];

        foreach ($resolvers as $resolver) {
            Resolver::firstOrCreate(
                ['address' => $resolver['address'], 'type' => $resolver['type']],
                $resolver
            );
        }

        // Create default group
        Group::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Group',
                'description' => 'Default monitoring group',
                'check_interval' => 300,
                'jitter_percent' => 15,
                'notifications_enabled' => true,
                'created_by' => $user->id,
            ]
        );

        // Create default shortlink group
        ShortlinkGroup::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Shortlink Group',
                'description' => 'Default shortlink rotation group',
                'rotation_threshold' => 3,
                'cooldown_seconds' => 300,
                'min_confidence' => 80,
                'auto_rollback' => true,
                'created_by' => $user->id,
            ]
        );
    }
}

