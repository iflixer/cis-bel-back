<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Video extends Model{

    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $guarded = [];

    // protected $fillable = [	
    //     'id',
    //     'id_VDB',
    //     'tupe',
    //     'name',
    //     'ru_name',
    //     'year',
    //     'kinopoisk',
    //     'imdb',
    //     'description',
    //     'img',
        
    //     'film_length',
    //     'slogan',
    //     'rating_mpaa',
    //     'rating_age_limits',
    //     'premiere_ru',
    //     'distributors',
    //     'premiere_world',
    //     'premiere_digital',
    //     'premiere_world_country',
    //     'premiere_dvd',
    //     'premiere_blu_ray',
    //     'distributor_release',
    //     'facts',
    //     'seasons',
    //     'lock',

    //     'quality',
    //     'created_at',
    //     'updated_at'
    // ]; // разрешенные поля для редактирования
}
