<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id_parent', 
        'name', 
        'status', 
        'player', 
        'show', 
        'lowshow', 
        'new_player', 
        'black_ad_on'
    ];
}
