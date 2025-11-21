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
    public function files()
    {
        return $this->hasMany(File::class, 'id_parent');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'link_genres', 'id_video', 'id_genre');
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'link_countries', 'id_video', 'id_country');
    }

    public function actors()
    {
        return $this->belongsToMany(Actor::class, 'link_actors', 'id_video', 'id_actor')
            ->withPivot('character_name');
    }

    public function directors()
    {
        return $this->belongsToMany(Director::class, 'link_directors', 'id_video', 'id_director');
    }
}
