<?php

namespace Sadpcd\ErrorAlerts\Tests\Unit;

use Sadpcd\ErrorAlerts\Services\ErrorThrottler;
use Sadpcd\ErrorAlerts\Tests\TestCase;

class ErrorThrottlerTest extends TestCase
{
    protected ErrorThrottler $throttler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->throttler = app(ErrorThrottler::class);
        $this->throttler->clear();
    }

    public function test_first_error_is_allowed_to_send(): void
    {
        $fingerprint = 'mock_fingerprint_' . uniqid();
        $result = $this->throttler->shouldSend($fingerprint);

        $this->assertTrue($result['should_send']);
        $this->assertEquals(1, $result['occurrences']);
    }

    public function test_subsequent_identical_error_within_cooldown_is_throttled(): void
    {
        $fingerprint = 'mock_fingerprint_' . uniqid();

        // 1st occurrence -> sends
        $first = $this->throttler->shouldSend($fingerprint);
        $this->assertTrue($first['should_send']);

        // 2nd occurrence -> throttled
        $second = $this->throttler->shouldSend($fingerprint);
        $this->assertFalse($second['should_send']);
        $this->assertEquals('cooldown_active', $second['reason']);

        // 3rd occurrence -> still throttled
        $third = $this->throttler->shouldSend($fingerprint);
        $this->assertFalse($third['should_send']);
    }

    public function test_burst_limiter_stops_excessive_different_errors(): void
    {
        config(['error-alerts.burst_limiter.max_alerts_per_hour' => 3]);

        // Error 1
        $r1 = $this->throttler->shouldSend('error_1');
        $this->assertTrue($r1['should_send']);

        // Error 2
        $r2 = $this->throttler->shouldSend('error_2');
        $this->assertTrue($r2['should_send']);

        // Error 3
        $r3 = $this->throttler->shouldSend('error_3');
        $this->assertTrue($r3['should_send']);

        // Error 4 -> Burst limit reached!
        $r4 = $this->throttler->shouldSend('error_4');
        $this->assertFalse($r4['should_send']);
        $this->assertEquals('burst_limit_exceeded', $r4['reason']);
    }
}
