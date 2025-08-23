<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class DebugSqlMiddleware
{
    public function handle($request, Closure $next)
    {
        // Если есть кука DEBUG_MYSQL, включаем логирование
        if ($request->cookie('DEBUG_MYSQL')) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        if ($request->cookie('DEBUG_MYSQL')) {
            $queries = DB::getQueryLog();

            // Красивый вывод прямо в конец HTML
            if ($response->headers->get('content-type') === 'text/html; charset=UTF-8') {
                $html = "<pre style='background:#111;color:#0f0;padding:10px;overflow:auto;'>"
                      . htmlspecialchars(print_r($queries, true))
                      . "</pre>";
                $response->setContent($response->getContent() . $html);
            }
        }

        return $response;
    }
}