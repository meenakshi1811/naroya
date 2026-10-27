<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ClinicTokenQueue extends Model
{
    protected $table = 'clinic_token_queues';

    protected $fillable = [
        'clinic_id',
        'queue_date',
        'current_token',
        'updated_by',
    ];

    protected $casts = [
        'current_token' => 'integer',
    ];

    protected static function booted(): void
    {
        // Keep the short-lived running-token cache honest: any change to the day's
        // queue row drops it, so the next read (including the writer's own response)
        // reflects the new token straight away instead of waiting out the TTL.
        $forget = fn (self $queue) => Cache::forget(
            self::runningTokenCacheKey($queue->clinic_id, (string) $queue->queue_date)
        );

        static::saved($forget);
        static::deleted($forget);
    }

    /** Cache key for a clinic's running token on a given day (Y-m-d). */
    public static function runningTokenCacheKey(int|string $clinicId, string $date): string
    {
        return "clinic_running_token:{$clinicId}:{$date}";
    }
}
