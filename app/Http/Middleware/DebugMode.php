<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class DebugMode
{
    public function handle($request, Closure $next)
    {
        if ($request->has('debuggy') && $request->get('debuggy')) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
            DB::enableQueryLog();
        }

        return $next($request);
    }
}