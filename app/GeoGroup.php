<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GeoGroup extends Model
{
    protected $table = 'geo_groups';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'name'
    ];

    public function countries()
    {
        return $this->hasMany('App\IsoCountry', 'geo_group_id');
    }
}