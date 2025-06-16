<?php

namespace App;
use Illuminate\Database\Eloquent\Model;



class PasswordReset extends Model
{
    //
    public $timestamps = false; // не записывать дату изменений
    
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = ['email', 'token']; // разрешенные поля для редактирования
}
