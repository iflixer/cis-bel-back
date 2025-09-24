<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public static function get_main_info($domain_name, $columns=[]) {
        if (empty($columns)) $columns = ['id', 'id_parent', 'status', 'domain_type_id'];
        return self::select($columns)->where('name', $domain_name)->first();
    }

}
