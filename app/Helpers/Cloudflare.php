<?php
namespace App\Helpers;

use DB;


class Cloudflare
{
    public static function visitor_ip(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    public static function visitor_country(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_COUNTRY'])) {
            return $_SERVER['HTTP_CF_CONNECTING_COUNTRY'];
        }
        return '';
    }
}


