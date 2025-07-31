<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Link_director extends Model
{
    public $timestamps = false;
    protected $fillable = [	
        'id_director',
        'id_video'
    ];
}
