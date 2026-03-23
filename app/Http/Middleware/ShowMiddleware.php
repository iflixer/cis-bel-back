<?php

namespace App\Http\Middleware;

use Closure;

use App\Domain;
use App\Helpers\Debug;
use App\Seting;

class ShowMiddleware{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){

        //if(isset($_SERVER['HTTP_REFERER']) || isset($_GET['domain']) ){

        /*if ($_SERVER['HTTP_REFERER'] && (parse_url($_SERVER['HTTP_REFERER'])['host'] == 'api.kholobok.biz' || parse_url($_SERVER['HTTP_REFERER'])['host'] == 'kholobok.biz')) {
            $request->domain = parse_url($_SERVER['HTTP_REFERER'])['host'];
            return $next($request);
        }*/

        // $domain = Domain::where('name', parse_url($_SERVER['HTTP_REFERER'])['host'])->first();

        $_domain = '';

        // логика определения домена
        // если есть параметр domain в гет и он содержит @, то берем его - приоритет гелеграм-канала
        // иначе если запрос из iframe, то берем домен из реферера - приоритет встраивания
        // иначе если есть параметр domain в гет и он валидный, то берем его - обычный приоритет
        // иначе пометим домен как неавторизованный
        // РАБОТАЕТ ПЛОХО!!! при переходах внутри ифрейма уже не посылается sec-fetch-dest а используется явно ?domain=
        // if (!empty($_GET['domain']) && str_contains($_GET['domain'], '@')) {
        //     $_domain = $_GET['domain'];
        // } else {
        //     $dest = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? '';
        //     if($dest == 'iframe' && !empty($_SERVER['HTTP_REFERER'])) {
        //         $_domain = parse_url($_SERVER['HTTP_REFERER'])['host'];
        //     } else {
        //         if (!empty($_GET['domain']) && preg_match("#^[a-z0-9-_.]+$#i", $_GET['domain'])) {
        //             $_domain = $_GET['domain'];
        //         }
        //     }
        // }


    
        if (!empty($_GET['domain'])) {
            $_domain = $_GET['domain'];
        } else {
            if(!empty($_SERVER['HTTP_REFERER'])) {
                $_domain = parse_url($_SERVER['HTTP_REFERER'])['host'];
            }
        }

// var_dump($dest);
// var_dump($_domain);
// die();
        if (empty($_domain)) {
            header('X-back-reason: ShowMiddleware domain not set');
            //abort(404); 
        } else {
            $request->domain = $_domain;
            $domain = Domain::where('name', $_domain)->first();

            // check if subdomain
            if (empty($domain)) { 
                $__domain = substr($_domain, strpos($_domain, '.') + 1, strlen($_domain));
                if (strpos($__domain, '.') !== false) {
                    $domain = Domain::where('name', $__domain)->first();
                    $request->domain = $__domain;
                }
            } else {
                $request->player = $domain->new_player;
            }
        }

        $request->domain_approved = false;
        if( !empty($domain) && $domain->status == '1' ){
            $request->domain_approved = true;
        }


        // проверяем капчу, если она есть
        $token = $request->input('cf-turnstile-response');

        // по умолчанию считаем что не ок
        $request->attributes->set('turnstile_ok', false);
        $request->attributes->set('turnstile_error_codes', []);
        $request->attributes->set('turnstile_raw', null);

        if ($token) {
            try {
                $secret = Seting::where('name', 'cloudflare_captcha_secret')->value('value') ?? '';

                $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    // 'secret'   => config('services.turnstile.secret'),
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(), // опционально
                ]));

                $raw = curl_exec($ch);
                $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if ($raw !== false && $status >= 200 && $status < 300) {
                    $data = json_decode($raw, true);
                    if (!is_array($data)) {
                        $request->attributes->set('turnstile_error_codes', ['verify_invalid_json']);
                        return $next($request);
                    }
                    $request->attributes->set('turnstile_raw', $data);

                    $ok = !empty($data['success']);
                    $request->attributes->set('turnstile_ok', $ok);
                    $request->attributes->set('turnstile_error_codes', $data['error-codes'] ?? []);
                } else {
                    if (!empty($curlErr)) {
                        $request->attributes->set('turnstile_raw', ['curl_error' => $curlErr]);
                    }
                    $request->attributes->set('turnstile_error_codes', ['verify_http_failed']);
                }
            } catch (\Throwable $e) {
                $request->attributes->set('turnstile_error_codes', ['verify_exception']);
            }
        }

        return $next($request);

        //}
        // header('X-back-reason: ShowMiddleware end');
        // abort(404);

        // return $next($request);

    }
}
