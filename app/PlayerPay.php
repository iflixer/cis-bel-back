<?php

namespace App;
use App\Helpers\Cloudflare;
use DB;

use Illuminate\Database\Eloquent\Model;

class PlayerPay extends Model
{
    protected $table = 'player_pay_log';
    public $timestamps = false;

    protected $guarded = [];

    protected static $event_names = ['load','play','pay','vast_complete','p25','p50','p75','p100','getads','impression','p1','loaderror']; // sync with ENUM in DB!!!!

    /**
     * Create player event record in DB
     */
    public static function save_event(string $event_name,  $domain, int $file_id)
    {
        if (empty($file_id)) return;
        if (!in_array($event_name, self::$event_names)) {
            return;
        }
        if (empty($domain)) {
            return;
        }

        $geo_group_id = (int)IsoCountry::get_group_id_by_iso(Cloudflare::visitor_country());
        self::create([
            'event' => $event_name,
            'user_id' => $domain->id_parent,
            'domain_id' => $domain->id,
            'domain_type_id' => $domain->domain_type_id ?? 0,
            'geo_group_id' => $geo_group_id ?? 0,
            'visitor_ip' =>  DB::raw("INET6_ATON('" . Cloudflare::visitor_ip() . "')"),
            'file_id' => $file_id ?? 0,
        ]);
    }

}
