<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Main extends Model
{
    
    // protected $table = 'nameTable'; // если используется другая таблица
    // public $incrementing = false; // отмена инкремента
    // public $timestamps = false; // не записывать дату изменений
    
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = ['text']; // разрешенные поля для редактирования
    // protected $guarded = ['text']; // запрещенные поля для редактирования


    // Связывание 1 - 1
    /*
    public function country(){
        return $this->hasOne('App\Model', [key], [key2]); // Модель для связи, 
        // [key] - поле внешнего ключа
        // [key2] - поле ключа привязанной таблицы
    }
    */

    


}
