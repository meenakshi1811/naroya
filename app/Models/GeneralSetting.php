<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $table = 'general_settings'; // Specify the table name

    protected $fillable = [
        'id',
        'field_name',
        'field_value',
    ];
}
