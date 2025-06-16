<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Videodb extends Model
{
	protected $primaryKey = 'id';
	protected $table = 'videodb';
	public $timestamps = false;
	protected $fillable = [
		'last_accepted_at',
		'method'
	];
}