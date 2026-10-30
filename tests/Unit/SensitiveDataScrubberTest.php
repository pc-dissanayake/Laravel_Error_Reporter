<?php

namespace Sadpcd\ErrorAlerts\Tests\Unit;

use Sadpcd\ErrorAlerts\Services\SensitiveDataScrubber;
use Sadpcd\ErrorAlerts\Tests\TestCase;

class SensitiveDataScrubberTest extends TestCase
{
    protected SensitiveDataScrubber $scrubber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scrubber = new SensitiveDataScrubber();
    }

    public function test_it_scrubs_passwords_and_tokens(): void
    {
        $payload = [
            'username'              => 'john_doe',
            'password'              => 'SuperSecret123!',
            'password_confirmation' => 'SuperSecret123!',
            'api_token'             => 'sk_live_1234567890abcdef',
            'nested'                => [
                'credit_card' => '4111111111111111',
                'description' => 'Valid input',
            ],
        ];

        $cleaned = $this->scrubber->scrub($payload);

        $this->assertEquals('john_doe', $cleaned['username']);
        $this->assertEquals('[REDACTED]', $cleaned['password']);
        $this->assertEquals('[REDACTED]', $cleaned['password_confirmation']);
        $this->assertEquals('[REDACTED]', $cleaned['api_token']);
        $this->assertEquals('[REDACTED]', $cleaned['nested']['credit_card']);
        $this->assertEquals('Valid input', $cleaned['nested']['description']);
    }

    public function test_it_masks_bearer_auth_headers(): void
    {
        $payload = [
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9',
            'Accept'        => 'application/json',
        ];

        $cleaned = $this->scrubber->scrub($payload);

        $this->assertEquals('[REDACTED]', $cleaned['Authorization']);
        $this->assertEquals('application/json', $cleaned['Accept']);
    }
}
