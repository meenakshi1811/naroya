<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;


    protected $table = 'payment_logs';


    protected $fillable = [
        'patient_id',
        'dr_id',
        'appointment_id',
        'payment_id',
        'varStatus',
        'amount',
        'transaction_time',
        'response',
        'description'
    ];
}
