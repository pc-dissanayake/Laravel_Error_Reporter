<?php

namespace Sadpcd\ErrorAlerts\Services;

class SensitiveDataScrubber
{
    /**
     * @var array<string>
     */
    protected array $scrubFields;

    public function __construct(?array $scrubFields = null)
    {
        $this->scrubFields = array_map('strtolower', $scrubFields ?? config('error-alerts.scrub_fields', []));
    }

    /**
     * Recursively scrub sensitive keys and patterns from an array.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function scrub(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $clean[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->scrub($value);
            } elseif (is_string($value)) {
                $clean[$key] = $this->scrubStringValue($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * Check if the array key indicates sensitive data.
     */
    protected function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        foreach ($this->scrubFields as $field) {
            if ($field !== '' && (str_contains($lowerKey, $field) || $lowerKey === $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask sensitive strings (e.g. Bearer tokens, JWTs, credit card patterns).
     */
    protected function scrubStringValue(string $value): string
    {
        // Bearer tokens
        if (preg_match('/^Bearer\s+.+$/i', $value)) {
            return 'Bearer [REDACTED]';
        }

        // Basic Auth
        if (preg_match('/^Basic\s+[a-zA-Z0-9+\/=]+$/i', $value)) {
            return 'Basic [REDACTED]';
        }

        return $value;
    }
}
