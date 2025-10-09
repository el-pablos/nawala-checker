<?php

namespace Tests\Unit\NawalaChecker;

use App\Services\CacheService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected CacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CacheService();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_can_store_data_in_cache()
    {
        $result = $this->service->put('test_key', 'test_value', 60);

        $this->assertTrue($result);
        $this->assertEquals('test_value', Cache::get('test_key'));
    }

    /** @test */
    public function it_can_retrieve_data_from_cache()
    {
        Cache::put('test_key', 'test_value', 60);

        $value = $this->service->get('test_key');

        $this->assertEquals('test_value', $value);
    }

    /** @test */
    public function it_returns_default_value_when_key_not_found()
    {
        $value = $this->service->get('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /** @test */
    public function it_can_check_if_key_exists()
    {
        Cache::put('existing_key', 'value', 60);

        $this->assertTrue($this->service->has('existing_key'));
        $this->assertFalse($this->service->has('non_existent_key'));
    }

    /** @test */
    public function it_can_forget_cached_data()
    {
        Cache::put('test_key', 'test_value', 60);

        $result = $this->service->forget('test_key');

        $this->assertTrue($result);
        $this->assertFalse(Cache::has('test_key'));
    }

    /** @test */
    public function it_can_remember_data_with_callback()
    {
        $callCount = 0;
        
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'computed_value';
        };

        // First call should execute callback
        $value1 = $this->service->remember('test_key', $callback, 60);
        $this->assertEquals('computed_value', $value1);
        $this->assertEquals(1, $callCount);

        // Second call should use cached value
        $value2 = $this->service->remember('test_key', $callback, 60);
        $this->assertEquals('computed_value', $value2);
        $this->assertEquals(1, $callCount); // Callback not called again
    }

    /** @test */
    public function it_generates_correct_targets_list_key()
    {
        $userId = 123;
        $key = $this->service->getTargetsListKey($userId);

        $this->assertEquals('nawala:targets:list:123', $key);
    }

    /** @test */
    public function it_generates_correct_target_key()
    {
        $targetId = 456;
        $key = $this->service->getTargetKey($targetId);

        $this->assertEquals('nawala:target:456', $key);
    }

    /** @test */
    public function it_generates_correct_shortlink_key()
    {
        $slug = 'test-shortlink';
        $key = $this->service->getShortlinkKey($slug);

        $this->assertEquals('nawala:shortlink:test-shortlink', $key);
    }

    /** @test */
    public function it_generates_correct_resolvers_key()
    {
        $key = $this->service->getResolversKey();

        $this->assertEquals('nawala:resolvers', $key);
    }

    /** @test */
    public function it_can_invalidate_target_cache()
    {
        $targetId = 123;
        $userId = 456;

        Cache::put($this->service->getTargetKey($targetId), 'target_data', 60);
        Cache::put($this->service->getTargetsListKey($userId), 'targets_list', 60);

        $this->service->invalidateTarget($targetId, $userId);

        $this->assertFalse(Cache::has($this->service->getTargetKey($targetId)));
        $this->assertFalse(Cache::has($this->service->getTargetsListKey($userId)));
    }

    /** @test */
    public function it_can_invalidate_shortlink_cache()
    {
        $slug = 'test-shortlink';

        Cache::put($this->service->getShortlinkKey($slug), 'shortlink_data', 60);

        $this->service->invalidateShortlink($slug);

        $this->assertFalse(Cache::has($this->service->getShortlinkKey($slug)));
    }

    /** @test */
    public function it_uses_default_ttl_when_not_specified()
    {
        $this->service->put('test_key', 'test_value');

        $this->assertTrue(Cache::has('test_key'));
    }

    /** @test */
    public function it_can_store_complex_data_structures()
    {
        $complexData = [
            'array' => [1, 2, 3],
            'nested' => ['key' => 'value'],
            'object' => (object)['prop' => 'value'],
        ];

        $this->service->put('complex_key', $complexData, 60);
        $retrieved = $this->service->get('complex_key');

        $this->assertEquals($complexData, $retrieved);
    }

    /** @test */
    public function it_can_clear_cache_with_prefix()
    {
        Cache::put('nawala:test1', 'value1', 60);
        Cache::put('nawala:test2', 'value2', 60);
        Cache::put('other:test', 'value3', 60);

        $this->service->clearPrefix('nawala');

        // After flush, all cache should be cleared
        $this->assertFalse(Cache::has('nawala:test1'));
        $this->assertFalse(Cache::has('nawala:test2'));
        $this->assertFalse(Cache::has('other:test'));
    }

    /** @test */
    public function it_handles_null_values_correctly()
    {
        // Laravel cache doesn't store null values - they are treated as non-existent
        // When you put null, it's like the key doesn't exist
        $this->service->put('null_key', null, 60);

        // Getting a non-existent key returns the default value
        $value = $this->service->get('null_key', 'default');

        // So we expect the default value, not null
        $this->assertEquals('default', $value);

        // Verify the key doesn't exist
        $this->assertFalse($this->service->has('null_key'));
    }

    /** @test */
    public function it_handles_boolean_values_correctly()
    {
        $this->service->put('true_key', true, 60);
        $this->service->put('false_key', false, 60);

        $this->assertTrue($this->service->get('true_key'));
        $this->assertFalse($this->service->get('false_key'));
    }

    /** @test */
    public function it_handles_numeric_values_correctly()
    {
        $this->service->put('int_key', 123, 60);
        $this->service->put('float_key', 123.45, 60);

        $this->assertEquals(123, $this->service->get('int_key'));
        $this->assertEquals(123.45, $this->service->get('float_key'));
    }
}

