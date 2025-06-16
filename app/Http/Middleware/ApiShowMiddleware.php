<?php

namespace App\Http\Middleware;

use Closure;

use App\Domain;

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
        return $next($request); 
        //abort(404);
    }
}
