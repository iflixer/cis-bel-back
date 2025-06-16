<?php

namespace App;
use Illuminate\Database\Eloquent\Model;



class LinkRight extends Model
{
    //
    public $timestamps = false; // не записывать дату изменений
    
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = ['id_user', 'id_rights']; // разрешенные поля для редактирования
}
