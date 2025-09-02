<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cdn extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'host', 
        'node_name', 
        'weight', 
        'active',
        'tx','rx','tx5m','rx5m','last_report'
    ];

    protected $casts = [
        'tx' => 'int',
        'rx' => 'int',
        'tx5m' => 'int',
        'rx5m' => 'int',
        'last_report' => 'datetime',
    ];
}
