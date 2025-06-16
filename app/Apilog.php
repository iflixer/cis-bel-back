<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Apilog extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public $timestamps = false; // не записывать дату изменений
    protected $fillable = [
        'id',
        'date',
        'count',
        'loading'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
       
    ];
}
