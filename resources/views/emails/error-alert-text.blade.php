[🚨 500 SERVER ERROR ALERT]
=========================================
App: {{ $context['app_name'] ?? 'Laravel' }} [{{ strtoupper($context['app_env'] ?? 'PRODUCTION') }}]
Timestamp: {{ $timestamp }}
Context Type: {{ strtoupper($context['context_type'] ?? 'HTTP') }}

@if($occurrences > 1)
⚠️ RECURRENCE: Occurred {{ $occurrences }} times during cooldown.
@endif

ERROR DETAILS:
--------------
Class: {{ $exceptionClass }}
Message: {{ $errorMessage }}
Location: {{ $file }}:{{ $line }}
Fingerprint: {{ $fingerprint }}

@if(isset($context['request']['url']))
REQUEST:
--------
URL: [{{ $context['request']['method'] ?? 'GET' }}] {{ $context['request']['url'] }}
IP: {{ $context['request']['ip'] ?? 'N/A' }}
User Agent: {{ $context['request']['user_agent'] ?? 'N/A' }}
@endif

@if(!empty($context['user']))
USER:
-----
ID: {{ $context['user']['id'] }} ({{ $context['user']['email'] ?? 'No email' }})
@endif

@if(!empty($context['stack_trace']))
STACK TRACE (Top Frames):
-------------------------
@foreach($context['stack_trace'] as $idx => $frame)
#{{ $idx }} {{ $frame['call'] }} ({{ $frame['file'] }}:{{ $frame['line'] }})
@endforeach
@endif

=========================================
Sent by SADPCD Laravel Error Alerts.
Cooldown: {{ config('error-alerts.throttle.minutes', 30) }} mins.
