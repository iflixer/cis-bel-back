<?php

namespace App\Services\VideoDb;

use Illuminate\Support\Facades\Redis;

class SyncProgressTracker
{
    const REDIS_PROGRESS_KEY_TEMPLATE = 'videodb:sync:%s:progress';
    const REDIS_LOCK_KEY_TEMPLATE = 'videodb:sync:%s:lock';
    const LOCK_TTL = 120;
    const PROGRESS_TTL = 86400;
    const HEALTH_TTL = 604800;

    protected $sortDirection;
    protected $progressKey;
    protected $lockKey;

    public function __construct($sortDirection = 'created')
    {
        $this->sortDirection = $sortDirection;
        $this->progressKey = sprintf(self::REDIS_PROGRESS_KEY_TEMPLATE, $sortDirection);
        $this->lockKey = sprintf(self::REDIS_LOCK_KEY_TEMPLATE, $sortDirection);
    }

    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    public function acquireLock()
    {
        $result = Redis::set($this->lockKey, time(), 'EX', self::LOCK_TTL, 'NX');
        return $result !== null;
    }

    public function releaseLock()
    {
        Redis::del($this->lockKey);
    }

    public function isLocked()
    {
        $lockTime = Redis::get($this->lockKey);

        if ($lockTime === null) {
            return null;
        }

        $ttl = Redis::ttl($this->lockKey);

        return [
            'locked' => true,
            'locked_at' => $lockTime,
            'locked_since' => time() - $lockTime,
            'ttl_remaining' => $ttl,
        ];
    }

    public function hasLock()
    {
        return Redis::exists($this->lockKey) > 0;
    }

    public function forceResetLock()
    {
        $this->releaseLock();
        Redis::del($this->progressKey);
    }

    public function initProgress(array $config)
    {
        $data = [
            'sort_direction' => $this->sortDirection,
            'offset' => 0,
            'total_processed' => 0,
            'max_records' => $config['max_records'] ?? null,
            'config' => json_encode($config),
            'created_at' => time(),
            'updated_at' => time(),
        ];

        Redis::hmset($this->progressKey, $data);
        Redis::expire($this->progressKey, self::PROGRESS_TTL);
    }

    public function getProgress()
    {
        if (!Redis::exists($this->progressKey)) {
            return null;
        }

        $data = Redis::hgetall($this->progressKey);

        if (isset($data['config'])) {
            $data['config'] = json_decode($data['config'], true);
        }

        $data['offset'] = (int)($data['offset'] ?? 0);
        $data['total_processed'] = (int)($data['total_processed'] ?? 0);
        $data['max_records'] = isset($data['max_records']) && $data['max_records'] !== ''
            ? (int)$data['max_records']
            : null;

        return $data;
    }

    public function updateProgress(int $newOffset, int $batchProcessed)
    {
        if (!Redis::exists($this->progressKey)) {
            return false;
        }

        $currentProcessed = (int)Redis::hget($this->progressKey, 'total_processed');
        $newTotalProcessed = $currentProcessed + $batchProcessed;

        Redis::hmset($this->progressKey, [
            'offset' => $newOffset,
            'total_processed' => $newTotalProcessed,
            'updated_at' => time(),
        ]);
        Redis::expire($this->progressKey, self::PROGRESS_TTL);

        return $newTotalProcessed;
    }

    public function hasReachedMaxRecords()
    {
        $progress = $this->getProgress();

        if (!$progress) {
            return false;
        }

        $maxRecords = $progress['max_records'];
        if ($maxRecords === null) {
            return false;
        }

        return $progress['total_processed'] >= $maxRecords;
    }

    public function clearProgress()
    {
        Redis::del($this->progressKey);
    }

    public function hasProgress()
    {
        return Redis::exists($this->progressKey) > 0;
    }

    public function getResumeState()
    {
        $progress = $this->getProgress();

        if (!$progress) {
            return null;
        }

        return [
            'offset' => $progress['offset'],
            'total_processed' => $progress['total_processed'],
            'max_records' => $progress['max_records'],
            'config' => $progress['config'] ?? null,
        ];
    }

    public function recordRunStart()
    {
        $now = time();
        $totalRuns = (int)Redis::hget($this->progressKey, 'total_runs') + 1;

        Redis::hmset($this->progressKey, [
            'last_run_started_at' => $now,
            'total_runs' => $totalRuns,
        ]);
        Redis::expire($this->progressKey, self::HEALTH_TTL);
    }

    public function recordSuccess($status)
    {
        $now = time();
        Redis::hmset($this->progressKey, [
            'last_run_completed_at' => $now,
            'last_success_at' => $now,
            'last_status' => $status,
            'consecutive_errors' => 0,
        ]);
        Redis::expire($this->progressKey, self::HEALTH_TTL);
    }

    public function recordError($errorMessage)
    {
        $now = time();
        $consecutiveErrors = (int)Redis::hget($this->progressKey, 'consecutive_errors') + 1;
        $totalErrors = (int)Redis::hget($this->progressKey, 'total_errors') + 1;

        Redis::hmset($this->progressKey, [
            'last_run_completed_at' => $now,
            'last_error_at' => $now,
            'last_error_message' => substr($errorMessage, 0, 500),
            'last_status' => 'error',
            'consecutive_errors' => $consecutiveErrors,
            'total_errors' => $totalErrors,
        ]);
        Redis::expire($this->progressKey, self::HEALTH_TTL);
    }

    public function getHealthStatus()
    {
        if (!Redis::exists($this->progressKey)) {
            return null;
        }

        $data = Redis::hgetall($this->progressKey);

        return [
            'sort_direction' => $this->sortDirection,
            'last_run_started_at' => isset($data['last_run_started_at']) ? (int)$data['last_run_started_at'] : null,
            'last_run_completed_at' => isset($data['last_run_completed_at']) ? (int)$data['last_run_completed_at'] : null,
            'last_success_at' => isset($data['last_success_at']) ? (int)$data['last_success_at'] : null,
            'last_error_at' => isset($data['last_error_at']) ? (int)$data['last_error_at'] : null,
            'last_error_message' => $data['last_error_message'] ?? null,
            'last_status' => $data['last_status'] ?? null,
            'consecutive_errors' => (int)($data['consecutive_errors'] ?? 0),
            'total_runs' => (int)($data['total_runs'] ?? 0),
            'total_errors' => (int)($data['total_errors'] ?? 0),
        ];
    }
}
