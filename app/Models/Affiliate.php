<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = [
        'doctor_id',
        'name',
        'code',
        'commission_rate',
        'is_active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patients::class, 'affiliate_id');
    }

    public function referralUrl(): string
    {
        return url('/refer/' . $this->code);
    }

    public static function generateUniqueCode(?string $preferred = null): string
    {
        if ($preferred) {
            $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $preferred));
            if ($normalized !== '' && ! static::where('code', $normalized)->exists()) {
                return $normalized;
            }
        }

        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
