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
        'iso_code',
        'geo_group_id'
    ];

    public function playerLocationLogs()
    {
        return $this->hasMany('App\PlayerLocationLog', 'country_id');
    }

    public function geoGroup()
    {
        return $this->belongsTo('App\GeoGroup', 'geo_group_id');
    }

    public static function get_group_id_by_iso(string $iso): ?int
    {
        return self::where('iso_code', $iso)->value('geo_group_id');
    }
}