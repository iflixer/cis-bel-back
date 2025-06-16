<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Seting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false; // не записывать дату изменений
    protected $fillable = [
        'id',
        'name',
        'value'
    ];

    
}