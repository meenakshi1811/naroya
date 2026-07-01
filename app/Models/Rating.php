<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'doctor_id', 'rating', 'varShortTitle', 'varReview'];

    /**
     * Total stars divided by number of reviews, e.g. (5+5+5+5+5+5) / 6 = 5.0
     */
    public static function averageRatingExpression(): string
    {
        return 'ROUND(IFNULL(SUM(CAST(rating AS DECIMAL(10,2))) / NULLIF(COUNT(id), 0), 0), 1)';
    }

    public static function scopeWithValidRating(Builder $query): Builder
    {
        return $query->whereNotNull('rating')->where('rating', '!=', '');
    }

    /**
     * @return array<int, array{ratings: float, review_count: int}>
     */
    public static function statsForDoctorIds(array $doctorIds): array
    {
        if ($doctorIds === []) {
            return [];
        }

        return static::query()
            ->select('doctor_id')
            ->selectRaw(static::averageRatingExpression() . ' as ratings')
            ->selectRaw('COUNT(id) as review_count')
            ->whereIn('doctor_id', $doctorIds)
            ->withValidRating()
            ->groupBy('doctor_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->doctor_id => [
                    'ratings' => (float) $row->ratings,
                    'review_count' => (int) $row->review_count,
                ],
            ])
            ->all();
    }

    public static function orderByAverageRatingDesc(Builder $query, string $userTable = 'users'): Builder
    {
        $averageExpression = static::averageRatingExpression();
        $validRatingsCondition = "ratings.doctor_id = {$userTable}.id AND rating IS NOT NULL AND rating != ''";

        return $query
            ->orderByRaw(
                "(SELECT {$averageExpression} FROM ratings WHERE {$validRatingsCondition}) DESC"
            )
            ->orderByRaw(
                "(SELECT COUNT(id) FROM ratings WHERE {$validRatingsCondition}) DESC"
            );
    }

    public static function syncDoctorAverage(int $doctorId): void
    {
        $stats = static::statsForDoctorIds([$doctorId]);
        $average = (string) ($stats[$doctorId]['ratings'] ?? 0);

        User::query()->where('id', $doctorId)->update(['rattings' => $average]);
    }

    /**
     * Define the inverse relationship to the User model (doctor).
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }
}
