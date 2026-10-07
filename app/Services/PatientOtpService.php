<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PatientOtpService
{
    private const CACHE_PREFIX = 'patient_phone_otp:';

    private const TTL_MINUTES = 10;

    public function cacheKey(string $normalizedPhone): string
    {
        return self::CACHE_PREFIX.$normalizedPhone;
    }

    public function store(string $normalizedPhone, string $otp): void
    {
        Cache::put(
            $this->cacheKey($normalizedPhone),
            $otp,
            now()->addMinutes(self::TTL_MINUTES)
        );
    }

    public function verify(string $normalizedPhone, string $otp): bool
    {
        $stored = Cache::get($this->cacheKey($normalizedPhone));

        if ($stored === null || ! hash_equals((string) $stored, $otp)) {
            return false;
        }

        Cache::forget($this->cacheKey($normalizedPhone));

        return true;
    }

    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
