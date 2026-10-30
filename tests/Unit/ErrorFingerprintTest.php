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

    protected function createError(string $message): Exception
    {
        return new Exception($message, 500);
    }

    public function test_it_generates_identical_hash_for_same_error(): void
    {
        $e1 = $this->createError("SQLSTATE[HY000]: General error");
        $e2 = $this->createError("SQLSTATE[HY000]: General error");

        $hash1 = $this->fingerprinter->generate($e1);
        $hash2 = $this->fingerprinter->generate($e2);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1));
    }

    public function test_it_normalizes_dynamic_ids_in_message(): void
    {
        $e1 = $this->createError("User 12345 not found in session");
        $e2 = $this->createError("User 98765 not found in session");

        $hash1 = $this->fingerprinter->generate($e1);
        $hash2 = $this->fingerprinter->generate($e2);

        $this->assertEquals($hash1, $hash2);
    }
}
