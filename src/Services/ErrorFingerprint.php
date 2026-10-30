<?php

namespace Sadpcd\ErrorAlerts\Services;

use Throwable;

class ErrorFingerprint
{
    /**
     * Compute a unique fingerprint for the exception.
     */
    public function generate(Throwable $exception): string
    {
        $class = get_class($exception);
        $file = $this->normalizePath($exception->getFile());
        $line = $exception->getLine();
        $message = $this->normalizeMessage($exception->getMessage());

        return hash('sha256', "{$class}|{$file}|{$line}|{$message}");
    }

    /**
     * Normalize file paths to avoid diffs across environments.
     */
    protected function normalizePath(string $path): string
    {
        $base = base_path();
        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), '/\\');
        }

        return $path;
    }

    /**
     * Normalize dynamic elements (IDs, numbers, hex, UUIDs) in the error message
     * to group repeating errors together effectively.
     */
    protected function normalizeMessage(string $message): string
    {
        // Replace UUIDs with :uuid
        $normalized = preg_replace(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
            ':uuid',
            $message
        );

        // Replace numeric IDs (3 or more digits) with :id
        $normalized = preg_replace('/\b\d{3,}\b/', ':id', $normalized ?? $message);

        // Replace memory / hex addresses (e.g., 0x7fff)
        $normalized = preg_replace('/0x[0-9a-fA-F]+/i', ':hex', $normalized ?? $message);

        return trim($normalized ?? $message);
    }
}
