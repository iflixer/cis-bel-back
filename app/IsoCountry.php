<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IsoCountry extends Model
{
    protected $table = 'countries_iso';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [	
        'name',
        'iso_code'
    ];

    public function playerLocationLogs()
    {
        return $this->hasMany('App\PlayerLocationLog', 'country_id');
    }
}