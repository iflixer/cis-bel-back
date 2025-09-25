<?php

namespace App\Http\Middleware;

use Closure;

use App\Domain;
use App\Helpers\Bot;

class ApiShowMiddleware{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        $request->inDomain = $_SERVER['HTTP_REFERER'];
        if (Bot::isBot($_SERVER['HTTP_USER_AGENT'])) {
            return response('Unauthorized.', 401);
        }
        return $next($request); 
        //abort(404);
    }
}
