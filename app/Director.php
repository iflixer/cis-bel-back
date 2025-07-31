<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Director extends Model
{
    public $timestamps = false;
    protected $fillable = [	
        'kinopoisk_id',
        'name_ru',
        'name_en',
        'poster_url'
    ];
}
