<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'country_master';


    protected $fillable = [
        'id',
        'countrycode', 
        'countryname',
        'code',
        'phonecode',
        'chrPublish'
    ];
}
