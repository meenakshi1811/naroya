<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
