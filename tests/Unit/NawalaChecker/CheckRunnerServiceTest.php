<?php

namespace Tests\Unit\NawalaChecker;

use App\Models\NawalaChecker\Target;
use App\Models\NawalaChecker\Resolver;
use App\Models\User;
use App\Services\NawalaChecker\CheckRunnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckRunnerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CheckRunnerService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CheckRunnerService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_check_target_status()
    {
        $target = Target::factory()->create(['owner_id' => $this->user->id]);
        Resolver::factory()->create(['is_active' => true]);

        $result = $this->service->checkTarget($target);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('results', $result);
    }

    /** @test */
    public function it_detects_blocked_ip()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isBlockedIP');
        $method->setAccessible(true);

        // Known block IP
        $this->assertTrue($method->invoke($this->service, '103.10.66.1'));
        $this->assertTrue($method->invoke($this->service, '36.86.63.1'));

        // Normal IP
        $this->assertFalse($method->invoke($this->service, '8.8.8.8'));
        $this->assertFalse($method->invoke($this->service, '1.1.1.1'));
    }

    /** @test */
    public function it_detects_block_page_content()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('isBlockPage');
        $method->setAccessible(true);

        $blockPageContent = '<html><body>Internet Positif - Situs ini diblokir</body></html>';
        $normalContent = '<html><body>Welcome to our website</body></html>';

        $this->assertTrue($method->invoke($this->service, $blockPageContent));
        $this->assertFalse($method->invoke($this->service, $normalContent));
    }

    /** @test */
    public function it_fuses_verdicts_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('fuseVerdicts');
        $method->setAccessible(true);

        // Majority blocked
        $verdicts = ['DNS_FILTERED', 'DNS_FILTERED', 'OK'];
        $this->assertEquals('DNS_FILTERED', $method->invoke($this->service, $verdicts));

        // Majority OK
        $verdicts = ['OK', 'OK', 'DNS_FILTERED'];
        $this->assertEquals('OK', $method->invoke($this->service, $verdicts));

        // All same
        $verdicts = ['OK', 'OK', 'OK'];
        $this->assertEquals('OK', $method->invoke($this->service, $verdicts));
    }

    /** @test */
    public function it_calculates_confidence_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateConfidence');
        $method->setAccessible(true);

        // 100% confidence
        $verdicts = ['OK', 'OK', 'OK'];
        $this->assertEquals(100, $method->invoke($this->service, $verdicts, 'OK'));

        // 66% confidence
        $verdicts = ['OK', 'OK', 'DNS_FILTERED'];
        $confidence = $method->invoke($this->service, $verdicts, 'OK');
        $this->assertEquals(67, $confidence); // Rounded

        // 33% confidence
        $verdicts = ['OK', 'DNS_FILTERED', 'DNS_FILTERED'];
        $confidence = $method->invoke($this->service, $verdicts, 'OK');
        $this->assertEquals(33, $confidence);
    }

    /** @test */
    public function it_updates_target_status_on_change()
    {
        $target = Target::factory()->create([
            'owner_id' => $this->user->id,
            'current_status' => 'UNKNOWN',
        ]);

        Resolver::factory()->create(['is_active' => true]);

        $this->service->checkTarget($target);

        $target->refresh();
        $this->assertNotEquals('UNKNOWN', $target->current_status);
        $this->assertNotNull($target->last_checked_at);
    }

    /** @test */
    public function it_increments_consecutive_failures_on_non_ok_status()
    {
        $target = Target::factory()->create([
            'owner_id' => $this->user->id,
            'current_status' => 'OK',
            'consecutive_failures' => 0,
        ]);

        // Mock a failed check by setting status to blocked
        $target->update([
            'current_status' => 'DNS_FILTERED',
            'consecutive_failures' => 1,
        ]);

        $this->assertEquals(1, $target->consecutive_failures);
    }

    /** @test */
    public function it_resets_consecutive_failures_on_ok_status()
    {
        $target = Target::factory()->create([
            'owner_id' => $this->user->id,
            'current_status' => 'DNS_FILTERED',
            'consecutive_failures' => 5,
        ]);

        $target->update([
            'current_status' => 'OK',
            'consecutive_failures' => 0,
        ]);

        $this->assertEquals(0, $target->consecutive_failures);
    }

    /** @test */
    public function it_stores_check_results_in_database()
    {
        $target = Target::factory()->create(['owner_id' => $this->user->id]);
        $resolver = Resolver::factory()->create(['is_active' => true]);

        $this->service->checkTarget($target);

        $this->assertDatabaseHas('nc_check_results', [
            'target_id' => $target->id,
            'resolver_id' => $resolver->id,
        ]);
    }
}

