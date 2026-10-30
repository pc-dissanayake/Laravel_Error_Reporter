<?php

namespace Sadpcd\ErrorAlerts\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Throwable;

class ContextCollector
{
    protected SensitiveDataScrubber $scrubber;

    public function __construct(SensitiveDataScrubber $scrubber)
    {
        $this->scrubber = $scrubber;
    }

    /**
     * Build the complete execution context array.
     *
     * @param Throwable $exception
     * @param string $contextType
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function collect(Throwable $exception, string $contextType = 'http', array $extra = []): array
    {
        $context = [
            'app_name'    => config('app.name', 'Laravel'),
            'app_env'     => app()->environment(),
            'app_url'     => config('app.url'),
            'php_version' => PHP_VERSION,
            'laravel_ver' => app()->version(),
            'timestamp'   => now()->toIso8601String(),
            'server_host' => gethostname() ?: 'unknown',
            'context_type'=> $contextType,
        ];

        // Capture Request Context if available
        if (app()->bound('request')) {
            /** @var Request $request */
            $request = app('request');
            $context['request'] = $this->collectRequestContext($request);
            $context['user'] = $this->collectUserContext();

            // Detect Inertia.js request
            if ($request->hasHeader('X-Inertia')) {
                $context['inertia'] = [
                    'is_inertia' => true,
                    'version'    => $request->header('X-Inertia-Version'),
                    'partial'    => $request->header('X-Inertia-Partial-Component'),
                ];
                if ($contextType === 'http') {
                    $context['context_type'] = 'inertia';
                }
            }

            // Detect Livewire request
            if ($request->hasHeader('X-Livewire') || str_contains($request->path(), 'livewire/update')) {
                $context['livewire'] = [
                    'is_livewire' => true,
                    'fingerprint' => $request->input('components.0.snapshot'),
                ];
                if ($contextType === 'http') {
                    $context['context_type'] = 'livewire';
                }
            }
        }

        // Merge any explicit extra context passed by listeners (e.g. Queue job, CLI command)
        if (!empty($extra)) {
            $context['extra'] = $this->scrubber->scrub($extra);
        }

        // Add formatted Stack Trace
        $context['stack_trace'] = $this->formatStackTrace($exception);

        return $context;
    }

    /**
     * Collect request info and scrub sensitive data.
     */
    protected function collectRequestContext(Request $request): array
    {
        try {
            $route = Route::current();
            $routeName = $route ? $route->getName() : null;
            $routeAction = $route ? $route->getActionName() : null;

            return [
                'url'          => $request->fullUrl(),
                'method'       => $request->method(),
                'ip'           => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'referrer'     => $request->header('referer'),
                'route_name'   => $routeName,
                'route_action' => $routeAction,
                'inputs'       => $this->scrubber->scrub($request->all()),
                'headers'      => $this->scrubber->scrub($this->filterHeaders($request->headers->all())),
            ];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Safely extract the currently authenticated user.
     */
    protected function collectUserContext(): ?array
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();

                return [
                    'id'    => $user->getAuthIdentifier(),
                    'email' => $user->email ?? null,
                    'name'  => $user->name ?? null,
                ];
            }
        } catch (Throwable) {
            // Silently ignore if auth cannot be determined (e.g. database down)
        }

        return null;
    }

    /**
     * Filter and normalize headers to exclude internal noisy webserver headers.
     */
    protected function filterHeaders(array $headers): array
    {
        $filtered = [];
        $allowed = ['content-type', 'accept', 'user-agent', 'x-inertia', 'x-livewire', 'referer', 'x-requested-with'];

        foreach ($headers as $key => $val) {
            $lower = strtolower($key);
            if (in_array($lower, $allowed, true) || str_starts_with($lower, 'x-')) {
                $filtered[$key] = is_array($val) ? implode(', ', $val) : $val;
            }
        }

        return $filtered;
    }

    /**
     * Format stack trace into clean lines.
     *
     * @return array<int, array{file: string, line: int|string, call: string, is_app: bool}>
     */
    public function formatStackTrace(Throwable $exception, int $maxFrames = 25): array
    {
        $frames = [];
        $base = base_path();

        foreach ($exception->getTrace() as $index => $frame) {
            if ($index >= $maxFrames) {
                break;
            }

            $file = $frame['file'] ?? '[internal function]';
            $line = $frame['line'] ?? '-';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';
            $function = $frame['function'] ?? '';

            $isApp = false;
            if (str_starts_with($file, $base)) {
                $relFile = ltrim(substr($file, strlen($base)), '/\\');
                $isApp = !str_starts_with($relFile, 'vendor/');
            } else {
                $relFile = $file;
            }

            $frames[] = [
                'file'   => $relFile,
                'line'   => $line,
                'call'   => "{$class}{$type}{$function}()",
                'is_app' => $isApp,
            ];
        }

        return $frames;
    }
}
