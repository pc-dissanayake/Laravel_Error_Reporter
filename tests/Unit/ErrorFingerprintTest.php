<?php

namespace Sadpcd\ErrorAlerts\Tests\Unit;

use Exception;
use Sadpcd\ErrorAlerts\Services\ErrorFingerprint;
use Sadpcd\ErrorAlerts\Tests\TestCase;

class ErrorFingerprintTest extends TestCase
{
    protected ErrorFingerprint $fingerprinter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fingerprinter = app(ErrorFingerprint::class);
    }

    public function test_it_generates_identical_hash_for_same_error(): void
    {
        $e1 = new Exception("SQLSTATE[HY000]: General error", 500);
        $e2 = new Exception("SQLSTATE[HY000]: General error", 500);

        $hash1 = $this->fingerprinter->generate($e1);
        $hash2 = $this->fingerprinter->generate($e2);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1));
    }

    public function test_it_normalizes_dynamic_ids_in_message(): void
    {
        // Two messages with different dynamic user IDs should produce identical fingerprints
        $e1 = new Exception("User 12345 not found in session", 500);
        $e2 = new Exception("User 98765 not found in session", 500);

        $hash1 = $this->fingerprinter->generate($e1);
        $hash2 = $this->fingerprinter->generate($e2);

        $this->assertEquals($hash1, $hash2);
    }
}
