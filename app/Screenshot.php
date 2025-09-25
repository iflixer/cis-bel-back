<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Screenshot extends Model
{
    protected $primaryKey = 'id'; // поле с уникальным индификатором
    public $timestamps = false; // не записывать дату изменений
    protected $guarded = [];
}
