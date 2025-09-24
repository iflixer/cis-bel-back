<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id_parent', 
        'name', 
        'status', 
        'player', 
        'show', 
        'lowshow', 
        'new_player', 
        'black_ad_on'
    ];

    public static function get_main_info($domain_name, $columns=[]) {
        if (empty($columns)) $columns = ['id', 'id_parent', 'status'];
        return self::select($columns)->where('name', $domain_name)->first();
    }

}
