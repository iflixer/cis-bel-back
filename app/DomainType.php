<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DomainType extends Model
{
    protected $fillable = [
        'name', 
        'value'
    ];

    public function domains()
    {
        return $this->hasMany(Domain::class, 'domain_type_id');
    }
}