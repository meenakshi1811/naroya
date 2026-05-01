<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VideoSession extends Model
{
    use HasFactory;



    protected $table = 'video_sessions';

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'channel_name',
        'doctor_token',
        'patient_token',
    ];
}
