<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subtitle extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = [
        'file_id',
        'track_num',
        'lang',
        'subtitle_type',
        'filename',
        'url',
    ];

    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}
