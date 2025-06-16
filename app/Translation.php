<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
	protected $primaryKey = 'id';
	public $timestamps = false;
	protected $fillable = [
		'id_VDB',
		'title',
		'tag',
		'priority'
	];
}