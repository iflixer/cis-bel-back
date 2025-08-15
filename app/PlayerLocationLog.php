<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlayerLocationLog extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'country_id',
        'video_id', 
        'domain_name'
    ];

    protected $dates = ['created_at'];

    public function country()
    {
        return $this->belongsTo('App\IsoCountry', 'country_id');
    }

    public function video()
    {
        return $this->belongsTo('App\Video');
    }
}