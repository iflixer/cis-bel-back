<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tiket extends Model
{
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = [	
        'id',
        'id_user',
        'tupe',
        'title',
        'status',
        'data'
    ]; // разрешенные поля для редактирования
}
