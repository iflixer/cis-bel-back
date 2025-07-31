<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Link_actor extends Model
{
    public $timestamps = false;
    protected $fillable = [	
        'id_actor',
        'id_video',
        'character_name'
    ];
}
