<?php

namespace App;
use App\Helpers\Cloudflare;

use Illuminate\Database\Eloquent\Model;

class PlayerPay extends Model
{
    protected $table = 'player_pay_log';
    public $timestamps = false;

    protected $guarded = [];

    protected static $event_names = ['get','play','pay']; // sync with ENUM in DB!!!!

    /**
     * Create player event record in DB
     */
    public static function save_event($event_name, $user_id, $domain_id, $geo_group_id, $file_id)
    {
        if (!in_array($event_name, self::$event_names)) {
            return;
        }

        self::create([
            'event' => $event_name,
            'user_id' => $user_id,
            'domain_id' => $domain_id,
            'geo_group_id' => $geo_group_id,
            'visitor_ip' => Cloudflare::visitor_ip(),
            'file_id' => $file_id,
        ]);
    }

}
