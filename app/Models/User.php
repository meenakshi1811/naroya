<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Pagination\LengthAwarePaginator;
// use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'surname',
        'category',
        'country',
        'state',
        'gmc_registration_no',
        'ind_registration_no',
        'uae_reg_no',
        'email_verified_at',
        'remember_token',
        'chrApproval',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
     public function ratings()
    {
        return $this->hasMany(Rating::class, 'doctor_id', 'id');
    }

    public static function formatDoctorListing($doctors, ?int $patientId = null): void
    {
        $doctorCollection = $doctors instanceof LengthAwarePaginator
            ? $doctors->getCollection()
            : collect($doctors);

        if ($doctorCollection->isEmpty()) {
            return;
        }

        $doctorIds = $doctorCollection->pluck('id')->filter()->unique()->values()->all();

        $ratingsMap = Rating::query()
            ->selectRaw('doctor_id, IFNULL(AVG(rating), 0) as ratings, IFNULL(COUNT(id), 0) as review_count')
            ->whereIn('doctor_id', $doctorIds)
            ->groupBy('doctor_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->doctor_id => [
                        'ratings' => (float) $item->ratings,
                        'review_count' => (int) $item->review_count,
                    ],
                ];
            })
            ->toArray();

        $favouriteMap = [];
        if (!is_null($patientId)) {
            $favouriteMap = Favourite::query()
                ->where('patinet_id', $patientId)
                ->whereIn('user_id', $doctorIds)
                ->pluck('chrFav', 'user_id')
                ->toArray();
        }

        $doctorCollection->transform(function ($doctor) use ($ratingsMap, $favouriteMap, $patientId) {
            $doctor->varProfile = !empty($doctor->varProfile)
                ? config('app.url') . 'api/docterprofile/' . $doctor->varProfile
                : 'null';

            $ratingData = $ratingsMap[$doctor->id] ?? ['ratings' => 0, 'review_count' => 0];
            $doctor->ratings = [
                [
                    'ratings' => $ratingData['ratings'],
                    'review_count' => $ratingData['review_count'],
                ],
            ];

            if (!is_null($patientId)) {
                $doctor->isFavouriteFlag = $favouriteMap[$doctor->id] ?? 'N';
            }

            return $doctor;
        });

        if ($doctors instanceof LengthAwarePaginator) {
            $doctors->setCollection($doctorCollection);
        }
    }

    public function speciality()
    {
        return $this->belongsTo(DrCategory::class, 'category');
    }

    public function countryRel()
    {
        return $this->belongsTo(CountryMaster::class, 'country');
    }

    public function stateRel()
    {
        return $this->belongsTo(State::class, 'state');
    }

    public function paymentLogs()
    {
        return $this->hasMany(PaymentLog::class, 'dr_id');
    }

    public function doctorCredit()
    {
        return $this->hasOne(DoctorCredit::class, 'dr_id');
    }

    public function categoryRel()
    {
        return $this->belongsTo(DrCategory::class, 'category');
    }

    public function experiences()
    {
        return $this->hasMany(OrgExperience::class, 'user_id');
    }


    public function blocks()
    {
        return $this->hasMany(Block::class, 'dr_id');
    }
}
