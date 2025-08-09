<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LinkDomainTag extends Model
{
    protected $table = 'link_domain_tags';

    public $timestamps = false;

    protected $fillable = [
        'id_domain',
        'id_tag'
    ];
}

