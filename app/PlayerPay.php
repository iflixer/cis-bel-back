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

    protected static $event_names = ['load','play','pay','vast_complete']; // sync with ENUM in DB!!!!

    /**
     * Create player event record in DB
     */
    public static function save_event(string $event_name, int $user_id, int $domain_id, int $domain_tag_id, int $geo_group_id, int $file_id)
    {
        if (!in_array($event_name, self::$event_names)) {
            return;
        }

        self::create([
            'event' => $event_name,
            'user_id' => $user_id,
            'domain_id' => $domain_id,
            'domain_tag_id' => $domain_tag_id,
            'geo_group_id' => $geo_group_id,
            'visitor_ip' =>  DB::raw("INET6_ATON('" . Cloudflare::visitor_ip() . "')"),
            'file_id' => $file_id,
        ]);
    }

}
