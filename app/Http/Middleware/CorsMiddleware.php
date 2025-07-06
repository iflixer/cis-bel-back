<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        $origin = $request->header('Origin');
        $allowedOrigins = $this->getAllowedOrigins();
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : '*';
        $allowedMethods = implode(', ', config('cors.allowed_methods', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']));
        $allowedHeaders = implode(', ', config('cors.allowed_headers', ['Origin', 'Content-Type', 'Accept', 'Authorization', 'X-Requested-With']));
        $allowCredentials = config('cors.allow_credentials', true) ? 'true' : 'false';
        $maxAge = config('cors.max_age', 86400);

        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Methods', $allowedMethods)
                ->header('Access-Control-Allow-Headers', $allowedHeaders)
                ->header('Access-Control-Allow-Credentials', $allowCredentials)
                ->header('Access-Control-Max-Age', $maxAge);
        }

        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', $allowedOrigin)
                 ->header('Access-Control-Allow-Methods', $allowedMethods)
                 ->header('Access-Control-Allow-Headers', $allowedHeaders)
                 ->header('Access-Control-Allow-Credentials', $allowCredentials);

        return $response;
    }

    protected function getAllowedOrigins()
    {
        $origins = config('cors.allowed_origins', []);
        $result = [];
        
        foreach ($origins as $origin) {
            if (empty($origin)) {
                continue;
            }

            if (strpos($origin, ',') !== false) {
                $splitOrigins = array_map('trim', explode(',', $origin));
                $result = array_merge($result, $splitOrigins);
            } else {
                $result[] = $origin;
            }
        }

        return array_unique(array_filter($result));
    }
}
