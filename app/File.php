<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class File extends Model{
    
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = [
        'id',
        'id_VDB',
        'id_parent',
        'path',
        'name',
        'ru_name',
        'season',
        'resolutions',
        'num',
        'translation_id',
        'translation',
        'created_at',
        'updated_at',
        'sids',
    ]; // разрешенные поля для редактирования


    public function video()
    {
        return $this->belongsTo(Video::class, 'id_parent');
    }

    public function translation()
    {
        return $this->belongsTo(Translation::class, 'translation_id');
    }

    public function subtitles()
    {
        return $this->hasMany(Subtitle::class, 'file_id');
    }

}
