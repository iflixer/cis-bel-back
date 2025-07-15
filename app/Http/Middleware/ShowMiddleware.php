<?php

namespace App\Http\Middleware;

use Closure;

use App\Domain;

class ShowMiddleware{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){

        
        if( isset($_SERVER['HTTP_REFERER']) ){

            /*if ($_SERVER['HTTP_REFERER'] && (parse_url($_SERVER['HTTP_REFERER'])['host'] == 'api.kholobok.biz' || parse_url($_SERVER['HTTP_REFERER'])['host'] == 'kholobok.biz')) {
                $request->domain = parse_url($_SERVER['HTTP_REFERER'])['host'];
                return $next($request);
            }*/

            // $domain = Domain::where('name', parse_url($_SERVER['HTTP_REFERER'])['host'])->first();

            $_domain = parse_url($_SERVER['HTTP_REFERER'])['host'];

            if (isset($_GET['domain']) && $_GET['domain'] && preg_match("#^[a-z0-9-_.]+$#i", $_GET['domain'])) {
                $_domain = $_GET['domain'];
            }

            $domain = Domain::where('name', $_domain)->first();
            $request->domain = $_domain;

            if (isset($_GET['d'])) {
                echo $request->domain;
                var_dump($domain);
                die();
            }

            // check if subdomain
            if ($domain === null) {
                $__domain = substr($_domain, strpos($_domain, '.') + 1, strlen($_domain));
                if (strpos($__domain, '.') !== false) {
                    $domain = Domain::where('name', $__domain)->first();
                    $request->domain = $__domain;
                }

                // if (isset($_GET['d'])) {
                //     echo $request->domain;
                //     var_dump($domain);
                // }
            }

            if( isset($domain) && $domain->status == '1' ){
                $request->player = $domain->new_player;
                // $request->domain = parse_url($_SERVER['HTTP_REFERER'])['host'];
                return $next($request);
            }
            

        }
        abort(404);

        // return $next($request);

    }
}
