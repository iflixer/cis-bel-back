<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id_parent', 
        'domain_type_id',
        'name', 
        'status', 
        'player', 
        'show', 
        'lowshow', 
        'new_player', 
        'black_ad_on'
    ];

    public function domainType()
    {
        return $this->belongsTo(DomainType::class, 'domain_type_id');
    }
}
