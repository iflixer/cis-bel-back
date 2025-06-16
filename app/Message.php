<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    protected $fillable = [	
        'id',
        'id_tiket',
        'message',
        'attachments',
        'id_user',
        'read'
    ]; // разрешенные поля для редактирования
}
