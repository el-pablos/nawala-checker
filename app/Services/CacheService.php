<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache TTL in seconds.
     */
    protected int $ttl = 3600; // 1 hour

    /**
     * Get cached data or execute callback and cache result.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? $this->ttl, $callback);
    }

    /**
     * Store data in cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl ?? $this->ttl);
    }

    /**
     * Get data from cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Check if key exists in cache.
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Remove data from cache.
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear all cache with specific prefix.
     */
    public function clearPrefix(string $prefix): void
    {
        // This is a simple implementation
        // In production, use Redis SCAN or similar for better performance
        Cache::flush();
    }

    /**
     * Get cache key for Nawala Checker targets list.
     */
    public function getTargetsListKey(int $userId): string
    {
        return "nawala:targets:list:{$userId}";
    }

    /**
     * Get cache key for Nawala Checker target detail.
     */
    public function getTargetKey(int $targetId): string
    {
        return "nawala:target:{$targetId}";
    }

    /**
     * Get cache key for shortlink.
     */
    public function getShortlinkKey(string $slug): string
    {
        return "nawala:shortlink:{$slug}";
    }

    /**
     * Get cache key for resolvers list.
     */
    public function getResolversKey(): string
    {
        return "nawala:resolvers";
    }

    /**
     * Invalidate target-related caches.
     */
    public function invalidateTarget(int $targetId, int $userId): void
    {
        $this->forget($this->getTargetKey($targetId));
        $this->forget($this->getTargetsListKey($userId));
    }

    /**
     * Invalidate shortlink-related caches.
     */
    public function invalidateShortlink(string $slug): void
    {
        $this->forget($this->getShortlinkKey($slug));
    }
}

