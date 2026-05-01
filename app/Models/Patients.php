<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Patients extends Authenticatable
{
    use HasApiTokens;
/**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */


    protected $table = 'patients';

    protected $fillable = [
        'name',
        'email',
        'password',
        'lastname',
        'country',
        'state',       
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

}
