<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Link_genre extends Model
{
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    public $timestamps = false; // не записывать дату изменений
    protected $fillable = [	
        'id_genre',
        'id_video'
    ]; // разрешенные поля для редактирования
}
