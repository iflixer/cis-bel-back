<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    public $timestamps = false; // не записывать дату изменений
    protected $fillable = [	
        'name'
    ]; // разрешенные поля для редактирования
}
