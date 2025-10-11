<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'login', 
        'email', 
        'password', 
        'api_key', 
        'status',
        'bearer_token',
        'refresh_token',
        'time_token',
        'name',
        'surname',
        'cent',
        'score',
        'domain_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token'
    ];

    public function transactions()
    {
        return $this->hasMany('App\UserTransaction', 'user_id');
    }

    public function getBalance()
    {
        return $this->transactions()->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return $this->getBalance();
    }
}
