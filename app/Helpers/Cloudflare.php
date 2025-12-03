<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Log;

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
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return $_SERVER['HTTP_CF_IPCOUNTRY'];
        }
        return '';
    }

    public static function check_captcha($token, $remoteIp, $secret): bool
    {
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret'   => $secret,
            'response' => $token,
        ];

        if (!empty($remoteIp)) {
            $data['remoteip'] = $remoteIp;
        }

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 5,
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            Log::warning('Turnstile request failed', [
                'token' => $token,
                'ip'    => $remoteIp,
            ]);
            return false;
        }

        $resultJson = json_decode($result, true);
        if (!$resultJson['success']) {
            Log::info('Turnstile failed', [
                'response' => $resultJson,
            ]);
            return false;
        }
        return true;
    }   
}


