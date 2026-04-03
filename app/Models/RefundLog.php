<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'refund_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_log_id',
        'patient_id',
        'doctor_id',
        'amount',
        'refund_id',
    ];

    /**
     * Get the payment log associated with this refund log.
     */
    public function paymentLog()
    {
        return $this->belongsTo(PaymentLog::class, 'payment_log_id');
    }

    /**
     * Get the patient associated with this refund log.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the doctor associated with this refund log.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
