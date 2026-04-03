<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorCredit extends Model
{
    use HasFactory;

    protected $table = 'doctor_credit';

    protected $fillable = [        
        'dr_id',
        'amount',
        'paidBy'
    ];

}
