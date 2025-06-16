<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Show extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = [
        'id',
        'id_domain',
        'id_ad',
        'id_video',
        'shows',
    ];

    
}