<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrgExperience extends Model
{
    protected $table = 'org_experiance';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'startYear',
        'endYear',
        'varDescription',
        'isCurrentworkOrg'
    ];
}