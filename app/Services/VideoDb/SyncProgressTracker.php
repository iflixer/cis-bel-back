<?php

namespace App\Services\VideoDb;

use Illuminate\Support\Facades\Redis;

class SyncProgressTracker
{
    const REDIS_PROGRESS_KEY = 'videodb:sync:progress';
    const REDIS_LOCK_KEY = 'videodb:sync:lock';
    const TTL = 3600;
    const RESUME_TTL = 86400; // 24 hours for resume capability

    public function acquireLock()
    {
        $result = Redis::set(self::REDIS_LOCK_KEY, time(), 'EX', self::TTL, 'NX');

        return $result !== null;
    }

    public function releaseLock()
    {
        Redis::del(self::REDIS_LOCK_KEY);
    }

    public function isLocked()
    {
        $lockTime = Redis::get(self::REDIS_LOCK_KEY);

        if ($lockTime === null) {
            return null;
        }

        $ttl = Redis::ttl(self::REDIS_LOCK_KEY);

        return [
            'locked' => true,
            'locked_at' => $lockTime,
            'locked_since' => time() - $lockTime,
            'ttl_remaining' => $ttl,
        ];
    }

    public function hasLock()
    {
        return Redis::exists(self::REDIS_LOCK_KEY) > 0;
    }

    public function forceResetLock()
    {
        $this->releaseLock();

        Redis::del(self::REDIS_PROGRESS_KEY);
    }

    public function start(array $metadata)
    {
        $key = self::REDIS_PROGRESS_KEY;

        $data = array_merge([
            'status' => 'running',
            'start_time' => time(),
            'current' => 0,
            'total' => 0,
            'errors' => json_encode([]),
            'last_processed_id' => null,
            'next_offset' => 0,
            'config' => json_encode([]),
        ], $metadata);

        if (isset($metadata['config'])) {
            $data['config'] = json_encode($metadata['config']);
        }

        Redis::hmset($key, $data);
        Redis::expire($key, self::TTL);
    }

    public function updateProgress(array $updates)
    {
        $key = self::REDIS_PROGRESS_KEY;

        if (!Redis::exists($key)) {
            return false;
        }

        $updates['updated_at'] = time();

        Redis::hmset($key, $updates);
        Redis::expire($key, self::TTL);

        return true;
    }

    public function addError(array $error)
    {
        $key = self::REDIS_PROGRESS_KEY;

        if (!Redis::exists($key)) {
            return false;
        }

        $errorsJson = Redis::hget($key, 'errors') ?: '[]';
        $errors = json_decode($errorsJson, true);

        $errors[] = array_merge($error, ['timestamp' => time()]);

        Redis::hset($key, 'errors', json_encode($errors));
        Redis::expire($key, self::TTL);

        return true;
    }

    public function complete(array $stats, $duration)
    {
        $key = self::REDIS_PROGRESS_KEY;

        Redis::hmset($key, [
            'status' => 'completed',
            'end_time' => time(),
            'duration' => $duration,
            'stats' => json_encode($stats),
        ]);

        Redis::expire($key, self::TTL);

        $this->releaseLock();
    }

    public function fail($error)
    {
        $key = self::REDIS_PROGRESS_KEY;

        Redis::hmset($key, [
            'status' => 'failed',
            'end_time' => time(),
            'error' => $error,
        ]);

        Redis::expire($key, self::TTL);

        $this->releaseLock();
    }

    public function getProgress()
    {
        $key = self::REDIS_PROGRESS_KEY;

        if (!Redis::exists($key)) {
            return null;
        }

        $data = Redis::hgetall($key);

        if (isset($data['errors'])) {
            $data['errors'] = json_decode($data['errors'], true);
        }
        if (isset($data['stats'])) {
            $data['stats'] = json_decode($data['stats'], true);
        }

        if ($data['total'] > 0) {
            $data['percentage'] = round(($data['current'] / $data['total']) * 100, 2);
        } else {
            $data['percentage'] = 0;
        }

        $data['can_resume'] = ($data['status'] === 'interrupted');

        return $data;
    }

    public function markInterrupted()
    {
        $key = self::REDIS_PROGRESS_KEY;

        if (!Redis::exists($key)) {
            return false;
        }

        Redis::hset($key, 'status', 'interrupted');
        Redis::hset($key, 'interrupted_at', time());
        Redis::expire($key, self::RESUME_TTL);

        $this->releaseLock();

        return true;
    }

    public function getResumeState()
    {
        $data = $this->getProgress();

        if (!$data || $data['status'] !== 'interrupted') {
            return null;
        }

        $config = [];
        if (isset($data['config'])) {
            $config = json_decode($data['config'], true) ?: [];
        }

        return [
            'config' => $config,
            'next_offset' => (int) ($data['next_offset'] ?? 0),
            'processed' => (int) ($data['current'] ?? 0),
            'interrupted_at' => $data['interrupted_at'] ?? null,
        ];
    }

    public function clearResumeState()
    {
        Redis::del(self::REDIS_PROGRESS_KEY);
    }
}
