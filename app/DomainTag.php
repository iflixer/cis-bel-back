<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DomainTag extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 
        'value',
        'type'
    ];

}
