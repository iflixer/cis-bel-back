<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CdnVideo extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cdn_id', 
        'video_id'
    ];
}
