<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BookCount extends Model
{
    use HasFactory;



    protected $table = 'book_count';


    protected $fillable = [
        'id',
        'patient_id',
        'dr_id',
        'varAppointment',
        'booked',
        'rebooked',
        'created_at',
        'updated_at'
    ];

}
