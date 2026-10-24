<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    public const LOGO_PATH = 'api/cliniclogo';

    protected $fillable = [
        'name',
        'registration_no',
        'email',
        'phone',
        'website',
        'address',
        'country',
        'state',
        'city_id',
        'postal_code',
        'timings',
        'max_tokens',
        'description',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'timings' => 'array',
        'max_tokens' => 'integer',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'clinic_id');
    }

    public function doctors(): HasMany
    {
        return $this->members()->where('role', User::ROLE_DOCTOR);
    }

    public function employees(): HasMany
    {
        return $this->members()->where('role', User::ROLE_EMPLOYEE);
    }

    public function countryRel(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country');
    }

    public function stateRel(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state');
    }

    public function cityRel(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset(self::LOGO_PATH . '/' . $this->logo) : null;
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = collect($words)
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'C';
    }

    /**
     * Sessions as display labels, e.g. ["9 AM – 1 PM", "4 PM – 8 PM"].
     */
    public function getTimingLabelsAttribute(): array
    {
        return collect($this->timings ?? [])
            ->map(fn (array $session) => self::formatTime($session['start']) . ' – ' . self::formatTime($session['end']))
            ->all();
    }

    private static function formatTime(string $time): string
    {
        $parsed = Carbon::createFromFormat('H:i', $time);

        return $parsed->format($parsed->minute === 0 ? 'g A' : 'g:i A');
    }

    /** One-line address, e.g. "12 Hill Road, Mumbai, Maharashtra, 400050, India". */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address,
            $this->cityRel?->name,
            $this->stateRel?->name,
            $this->postal_code,
            $this->countryRel?->countryname,
        ])->filter()->implode(', ');
    }

    public function getLocationLabelAttribute(): string
    {
        return collect([
            $this->cityRel?->name,
            $this->stateRel?->name,
        ])->filter()->implode(', ');
    }
}
