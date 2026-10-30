<?php

namespace Sadpcd\ErrorAlerts\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

class ErrorThrottler
{
    /**
     * Fallback file path when cache driver (Redis/DB) is down.
     */
    protected string $fallbackFilePath;

    public function __construct()
    {
        $this->fallbackFilePath = storage_path('framework/cache/error_alerts_cooldowns.json');
    }

    /**
     * Evaluate if an alert should be sent or throttled.
     *
     * @param string $fingerprint
     * @return array{should_send: bool, occurrences: int, reason: ?string}
     */
    public function shouldSend(string $fingerprint): array
    {
        $throttleEnabled = config('error-alerts.throttle.enabled', true);
        $minutes = (int) config('error-alerts.throttle.minutes', 30);
        $burstEnabled = config('error-alerts.burst_limiter.enabled', true);
        $burstLimit = (int) config('error-alerts.burst_limiter.max_alerts_per_hour', 15);

        // If throttling is turned off, always send
        if (!$throttleEnabled) {
            return ['should_send' => true, 'occurrences' => 1, 'reason' => null];
        }

        try {
            return $this->evaluateUsingCache($fingerprint, $minutes, $burstEnabled, $burstLimit);
        } catch (Throwable) {
            // Failover to local file storage if Redis / DB Cache crashes
            return $this->evaluateUsingFile($fingerprint, $minutes, $burstEnabled, $burstLimit);
        }
    }

    /**
     * Cache-based rate limiting evaluation.
     */
    protected function evaluateUsingCache(string $fingerprint, int $minutes, bool $burstEnabled, int $burstLimit): array
    {
        $burstKey = 'error_alerts:burst:' . date('YmdH');
        $lockKey = 'error_alerts:throttle:' . $fingerprint;
        $countKey = 'error_alerts:count:' . $fingerprint;

        // 1. Check Global Burst Limiter
        if ($burstEnabled) {
            $burstCount = (int) Cache::get($burstKey, 0);
            if ($burstCount >= $burstLimit) {
                return ['should_send' => false, 'occurrences' => 0, 'reason' => 'burst_limit_exceeded'];
            }
        }

        // 2. Check Per-Error Lock
        if (Cache::has($lockKey)) {
            // Error is throttled: increment occurrences count
            $occurrences = (int) Cache::increment($countKey);

            return ['should_send' => false, 'occurrences' => $occurrences, 'reason' => 'cooldown_active'];
        }

        // 3. New error: set lock & record burst
        $previousOccurrences = (int) Cache::get($countKey, 1);
        Cache::put($lockKey, true, now()->addMinutes($minutes));
        Cache::put($countKey, 1, now()->addMinutes($minutes * 2));

        // Track key in active list for clearing
        $trackedKeys = (array) Cache::get('error_alerts:active_keys', []);
        if (!in_array($fingerprint, $trackedKeys, true)) {
            $trackedKeys[] = $fingerprint;
            Cache::put('error_alerts:active_keys', $trackedKeys, now()->addDays(2));
        }

        if ($burstEnabled) {
            Cache::put($burstKey, (int) Cache::get($burstKey, 0) + 1, now()->addHour());
        }

        return ['should_send' => true, 'occurrences' => max(1, $previousOccurrences), 'reason' => null];
    }

    /**
     * Standalone file-based rate limiting fallback (Zero reliance on Cache/DB).
     */
    protected function evaluateUsingFile(string $fingerprint, int $minutes, bool $burstEnabled, int $burstLimit): array
    {
        $data = $this->readFileData();
        $now = time();
        $hourKey = date('YmdH');

        // Check Burst
        if ($burstEnabled) {
            $currentBurst = $data['burst'][$hourKey] ?? 0;
            if ($currentBurst >= $burstLimit) {
                return ['should_send' => false, 'occurrences' => 0, 'reason' => 'burst_limit_exceeded'];
            }
        }

        // Check Cooldown
        $lockExpiresAt = $data['locks'][$fingerprint] ?? 0;
        if ($now < $lockExpiresAt) {
            $count = ($data['counts'][$fingerprint] ?? 1) + 1;
            $data['counts'][$fingerprint] = $count;
            $this->saveFileData($data);

            return ['should_send' => false, 'occurrences' => $count, 'reason' => 'cooldown_active'];
        }

        // Lock is expired or new
        $occurrences = $data['counts'][$fingerprint] ?? 1;
        $data['locks'][$fingerprint] = $now + ($minutes * 60);
        $data['counts'][$fingerprint] = 1;

        if ($burstEnabled) {
            $data['burst'][$hourKey] = ($data['burst'][$hourKey] ?? 0) + 1;
        }

        $this->saveFileData($data);

        return ['should_send' => true, 'occurrences' => max(1, $occurrences), 'reason' => null];
    }

    /**
     * Read local lock data safely.
     */
    protected function readFileData(): array
    {
        if (file_exists($this->fallbackFilePath)) {
            $content = @file_get_contents($this->fallbackFilePath);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return ['locks' => [], 'counts' => [], 'burst' => []];
    }

    /**
     * Persist local lock data safely.
     */
    protected function saveFileData(array $data): void
    {
        try {
            $dir = dirname($this->fallbackFilePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($this->fallbackFilePath, json_encode($data), LOCK_EX);
        } catch (Throwable) {
            // Ignore if filesystem is read-only
        }
    }

    /**
     * Clear active throttles.
     */
    public function clear(?string $fingerprint = null): void
    {
        try {
            if ($fingerprint) {
                Cache::forget('error_alerts:throttle:' . $fingerprint);
                Cache::forget('error_alerts:count:' . $fingerprint);
            } else {
                $trackedKeys = (array) Cache::get('error_alerts:active_keys', []);
                foreach ($trackedKeys as $key) {
                    Cache::forget('error_alerts:throttle:' . $key);
                    Cache::forget('error_alerts:count:' . $key);
                }
                Cache::forget('error_alerts:active_keys');
                Cache::forget('error_alerts:burst:' . date('YmdH'));
            }
        } catch (Throwable) {
            // ignore
        }

        try {
            if (file_exists($this->fallbackFilePath)) {
                @unlink($this->fallbackFilePath);
            }
        } catch (Throwable) {
            // ignore
        }
    }
}
