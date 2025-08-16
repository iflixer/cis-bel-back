<?php

namespace App\Services;

use App\UserActionLog;

class UserActionLoggingService
{
    protected $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
        'bearer_token',
        'refresh_token'
    ];

    public function sanitizeRequestData(array $data)
    {
        $sanitized = $data;
        
        foreach ($this->getSensitiveFields() as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '[FILTERED]';
            }
        }

        $jsonData = json_encode($sanitized);
        $maxLength = config('user_action_logging.max_request_data_length');
        
        if (strlen($jsonData) > $maxLength) {
            $jsonData = substr($jsonData, 0, $maxLength) . '... [TRUNCATED]';
        }

        return json_decode($jsonData, true);
    }

    protected function getSensitiveFields()
    {
        $configFields = config('user_action_logging.exclude_sensitive_fields');

        return array_merge($this->sensitiveFields, $configFields);
    }

    public function getUserLogs($userId, $limit = 100, $offset = 0)
    {
        return UserActionLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getActionLogs($action, $limit = 100, $offset = 0)
    {
        return UserActionLog::where('action', $action)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getLogsInDateRange($startDate, $endDate, $limit = 100, $offset = 0)
    {
        return UserActionLog::whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function cleanOldLogs($days = 90)
    {
        $cutoffDate = now()->subDays($days);
        
        return UserActionLog::where('created_at', '<', $cutoffDate)->delete();
    }

    public function getActionStats($userId = null, $days = 30)
    {
        $query = UserActionLog::where('created_at', '>=', now()->subDays($days));
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();
    }
}