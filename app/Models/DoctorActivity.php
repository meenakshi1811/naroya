<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorActivity extends Model
{
    protected $fillable = [
        'doctor_id',
        'activity_type',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
