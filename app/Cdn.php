<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cdn extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'host', 
        'weight', 
        'active'
    ];
}
