<?php

namespace Sadpcd\ErrorAlerts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $fingerprint
 * @property string $exception_class
 * @property string $message
 * @property string $file
 * @property int $line
 * @property string|null $code
 * @property string|null $url
 * @property string|null $method
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $user_id
 * @property string|null $user_email
 * @property string $context_type
 * @property array|null $context_data
 * @property string|null $stack_trace
 * @property int $occurrences_count
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ErrorAlert extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'context_data'      => 'array',
        'last_seen_at'      => 'datetime',
        'resolved_at'       => 'datetime',
        'occurrences_count' => 'integer',
        'line'              => 'integer',
    ];

    public function getTable(): string
    {
        return config('error-alerts.database.table', 'error_alerts');
    }

    /**
     * Scope for unresolved errors.
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('status', 'unresolved');
    }

    /**
     * Scope for resolved errors.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for errors seen within N hours.
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('last_seen_at', '>=', now()->subHours($hours));
    }

    /**
     * Mark this error as resolved.
     */
    public function markAsResolved(): bool
    {
        return $this->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark this error as ignored.
     */
    public function markAsIgnored(): bool
    {
        return $this->update([
            'status' => 'ignored',
        ]);
    }

    /**
     * Reopen this error.
     */
    public function markAsUnresolved(): bool
    {
        return $this->update([
            'status'      => 'unresolved',
            'resolved_at' => null,
        ]);
    }
}
