<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Ad extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    public $timestamps = false; // не записывать дату изменений
    protected $fillable = [
        'id',
        'type',
        'body',
        'name',
        'sale',
        'procent',
        'position',
        'black_ad'
    ];

    
}