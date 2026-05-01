<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;

    protected $table = 'block';


    protected $fillable = [
        'id',
        'patient_id', 
        'dr_id', 
        'chrIsBlock',
        'varReason',
        'created_at',
        'updated_at'
    ];
}
