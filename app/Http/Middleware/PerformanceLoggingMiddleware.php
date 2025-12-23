<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceLoggingMiddleware
{
    protected $slowThreshold = 500;
    protected $criticalThreshold = 2000;
    protected $slowQueryThreshold = 100;

    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);

        DB::enableQueryLog();

        $response = $next($request);

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($totalTime >= $this->slowThreshold) {
            $this->logPerformance($request, $response, $totalTime);
        }

        $response->headers->set('X-Request-Time', $totalTime . 'ms');

        return $response;
    }

    protected function logPerformance($request, $response, $totalTime)
    {
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $queryTime = 0;
        $slowQueries = [];

        foreach ($queries as $query) {
            $queryTime += $query['time'];
            if ($query['time'] > $this->slowQueryThreshold) {
                $slowQueries[] = $query['query'] . ' (' . $query['time'] . 'ms)';
            }
        }

        $peakMemoryMB = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $phpTime = round($totalTime - $queryTime, 2);

        $logData = [
            'url' => $request->path(),
            'method' => $request->method(),
            'total_ms' => $totalTime,
            'db_ms' => round($queryTime, 2),
            'php_ms' => $phpTime,
            'queries' => $queryCount,
            'memory_mb' => $peakMemoryMB,
            'status' => $response->getStatusCode(),
        ];

        if (!empty($slowQueries)) {
            $logData['slow_queries'] = $slowQueries;
        }

        if ($totalTime >= $this->criticalThreshold || $queryCount > 50) {
            Log::warning('SLOW_REQUEST', $logData);
        } else {
            Log::info('SLOW_REQUEST', $logData);
        }
    }
}
