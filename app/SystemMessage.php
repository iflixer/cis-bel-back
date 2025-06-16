<?php

namespace App;
use Illuminate\Database\Eloquent\Model;



class SystemMessage extends Model
{
    //
    public $timestamps = false; // не записывать дату изменений
    
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = ['type', 'text']; // разрешенные поля для редактирования
}
