<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚨 500 Error Alert</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f3f4f6; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 680px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid #e5e7eb;">
                    <!-- Red Header -->
                    <tr>
                        <td style="background-color: #dc2626; padding: 20px 24px; color: #ffffff;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.9;">
                                            🚨 {{ strtoupper($context['app_env'] ?? 'PRODUCTION') }} ALERT
                                        </div>
                                        <div style="font-size: 22px; font-weight: 800; margin-top: 4px;">
                                            HTTP 500 Server Error
                                        </div>
                                    </td>
                                    <td align="right" style="vertical-align: middle;">
                                        <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600;">
                                            {{ $context['app_name'] ?? 'Laravel' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Recurrence Alert if throttled previously -->
                    @if($occurrences > 1)
                    <tr>
                        <td style="background-color: #fef2f2; border-bottom: 1px solid #fecaca; padding: 12px 24px; color: #991b1b; font-size: 13px; font-weight: 600;">
                            ⚠️ <strong>Recurring Error:</strong> This exception has triggered <strong>{{ $occurrences }} times</strong> during the active cooldown cycle.
                        </td>
                    </tr>
                    @endif

                    <!-- Main Body -->
                    <tr>
                        <td style="padding: 24px;">
                            <!-- Exception Title & Message -->
                            <div style="background-color: #f9fafb; border-left: 4px solid #dc2626; padding: 16px; border-radius: 4px; margin-bottom: 20px;">
                                <div style="font-size: 13px; font-family: monospace; color: #6b7280; margin-bottom: 6px;">
                                    {{ $exceptionClass }}
                                </div>
                                <div style="font-size: 16px; font-weight: 700; color: #111827; word-break: break-word; line-height: 1.4;">
                                    {{ $errorMessage ?: 'No exception message provided.' }}
                                </div>
                            </div>

                            <!-- Location Box -->
                            <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px; font-size: 13px;">
                                <tr>
                                    <td style="background-color: #f3f4f6; padding: 10px 14px; border-radius: 6px; font-family: monospace; color: #374151; word-break: break-all;">
                                        📁 <strong>{{ $file }}</strong>:<strong>{{ $line }}</strong>
                                    </td>
                                </tr>
                            </table>

                            <!-- Context Details Grid -->
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 8px;">
                                Execution Context
                            </div>
                            <table width="100%" cellspacing="0" cellpadding="8" style="border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 20px; font-size: 13px; border-collapse: collapse;">
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td width="30%" style="color: #6b7280; font-weight: 600; background: #fafafa;">Type</td>
                                    <td style="color: #111827;">
                                        <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase;
                                            @if(($context['context_type'] ?? '') === 'livewire') background: #fb7185; color: #fff;
                                            @elseif(($context['context_type'] ?? '') === 'inertia') background: #818cf8; color: #fff;
                                            @elseif(($context['context_type'] ?? '') === 'queue') background: #f59e0b; color: #fff;
                                            @else background: #3b82f6; color: #fff;
                                            @endif">
                                            {{ strtoupper($context['context_type'] ?? 'HTTP') }}
                                        </span>
                                    </td>
                                </tr>
                                @if(isset($context['request']['url']))
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="color: #6b7280; font-weight: 600; background: #fafafa;">URL</td>
                                    <td style="color: #111827; word-break: break-all; font-family: monospace;">
                                        <span style="font-weight: 700; color: #4b5563;">[{{ $context['request']['method'] ?? 'GET' }}]</span>
                                        {{ $context['request']['url'] }}
                                    </td>
                                </tr>
                                @endif
                                @if(!empty($context['request']['route_name']))
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="color: #6b7280; font-weight: 600; background: #fafafa;">Route Name</td>
                                    <td style="color: #111827; font-family: monospace;">{{ $context['request']['route_name'] }}</td>
                                </tr>
                                @endif
                                @if(!empty($context['request']['ip']))
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="color: #6b7280; font-weight: 600; background: #fafafa;">Client IP</td>
                                    <td style="color: #111827; font-family: monospace;">{{ $context['request']['ip'] }}</td>
                                </tr>
                                @endif
                                @if(!empty($context['request']['user_agent']))
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="color: #6b7280; font-weight: 600; background: #fafafa;">User Agent</td>
                                    <td style="color: #4b5563; font-size: 11px;">{{ $context['request']['user_agent'] }}</td>
                                </tr>
                                @endif
                                @if(!empty($context['user']))
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="color: #6b7280; font-weight: 600; background: #fafafa;">Auth User</td>
                                    <td style="color: #111827;">
                                        ID: <strong>{{ $context['user']['id'] }}</strong>
                                        @if(!empty($context['user']['email'])) | {{ $context['user']['email'] }} @endif
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="color: #6b7280; font-weight: 600; background: #fafafa;">Timestamp</td>
                                    <td style="color: #111827;">{{ $timestamp }} ({{ config('app.timezone', 'UTC') }})</td>
                                </tr>
                            </table>

                            <!-- Request Payload (Scrubbed) if present -->
                            @if(!empty($context['request']['inputs']))
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 8px;">
                                Request Payload (Sanitized)
                            </div>
                            <pre style="background-color: #1e293b; color: #e2e8f0; padding: 12px; border-radius: 6px; font-size: 11px; overflow-x: auto; margin-top: 0; margin-bottom: 20px;">{{ json_encode($context['request']['inputs'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @endif

                            <!-- Stack Trace Top Frames -->
                            @if(!empty($context['stack_trace']))
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; margin-bottom: 8px;">
                                Stack Trace (Top Application Frames)
                            </div>
                            <div style="background-color: #0f172a; border-radius: 6px; padding: 12px; margin-bottom: 20px; overflow-x: auto;">
                                <table width="100%" cellspacing="0" cellpadding="4" style="font-family: monospace; font-size: 11px; color: #94a3b8;">
                                    @foreach($context['stack_trace'] as $idx => $frame)
                                    <tr style="{{ $frame['is_app'] ? 'background-color: #1e293b; color: #38bdf8;' : '' }}">
                                        <td width="30" style="opacity: 0.6; vertical-align: top;">#{{ $idx }}</td>
                                        <td style="word-break: break-all;">
                                            <div><strong>{{ $frame['call'] }}</strong></div>
                                            <div style="opacity: 0.8; font-size: 10px;">{{ $frame['file'] }}:{{ $frame['line'] }}</div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            </div>
                            @endif

                            <!-- Fingerprint Info -->
                            <div style="font-size: 11px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 16px;">
                                Fingerprint: <code style="font-family: monospace;">{{ $fingerprint }}</code><br>
                                Throttling Window: {{ config('error-alerts.throttle.minutes', 30) }} minutes cooldown per unique error.
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 24px; text-align: center; font-size: 11px; color: #6b7280;">
                            Sent automatically by <strong>SADPCD Laravel Error Alerts</strong> to <strong>{{ implode(', ', (array) config('error-alerts.recipients')) }}</strong>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
