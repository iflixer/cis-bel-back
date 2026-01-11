<?php

namespace App\Http\Middleware;

use Closure;

use App\Domain;
use App\Helpers\Debug;

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
        return $next($request);

        //}
        // header('X-back-reason: ShowMiddleware end');
        // abort(404);

        // return $next($request);

    }
}
