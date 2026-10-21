<?php

namespace App\Models;

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
        'description',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function getLocationLabelAttribute(): string
    {
        return collect([
            $this->cityRel?->name,
            $this->stateRel?->name,
        ])->filter()->implode(', ');
    }
}
