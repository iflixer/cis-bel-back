<?php

namespace App\Http\Middleware;

use App\UserActionLog;
use App\Services\UserActionLoggingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserActionLoggingMiddleware
{
    protected $loggingService;

    public function __construct()
    {
        $this->loggingService = new UserActionLoggingService();
    }

    public function handle($request, Closure $next)
    {
        /** @var Request $request */
        if (!$this->shouldLog($request)) {
            return $next($request);
        }

        $userId = $request->userId ?? null;
        $action = $this->extractAction($request);
        $requestData = $this->loggingService->sanitizeRequestData($request->all());
        $httpMethod = $request->method();
        $url = $request->getRequestUri();

        $response = $next($request);

        $this->logAction($userId, $action, $requestData, $httpMethod, $url, $response->getStatusCode());

        return $response;
    }

    protected function shouldLog($request)
    {
        if (!config('user_action_logging.enabled', true)) {
            return false;
        }

        if (!isset($request->userId)) {
            return false;
        }

        $action = $this->extractAction($request);
        $excludedMethods = config('user_action_logging.exclude_methods');

        return !in_array($action, $excludedMethods);
    }

    protected function extractAction($request)
    {
        $method = $request->route('method');
        if (!$method) {
            return $request->path();
        }
        return $method;
    }

    protected function logAction($userId, $action, $requestData, $httpMethod, $url, $statusCode)
    {
        try {
            UserActionLog::logAction($userId, $action, $requestData, $httpMethod, $url, $statusCode);
        } catch (\Exception $e) {
            Log::error('Failed to log user action: ' . $e->getMessage());
        }
    }
}