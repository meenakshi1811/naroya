<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'bank_name',
        'account_holder_name',
        'account_type',
        'account_number',
        'ifsc_code',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
