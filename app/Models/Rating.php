<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;
    protected $fillable = ['patient_id', 'doctor_id', 'rating','varShortTitle', 'varReview'];

    /**
     * Define the inverse relationship to the User model (doctor).
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id', 'id');
    }
}
