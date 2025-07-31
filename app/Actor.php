<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [	
        'kinopoisk_id',
        'name_ru',
        'name_en',
        'poster_url'
    ];
}
