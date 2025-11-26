<?php

namespace App\Services\VideoDb;

use Illuminate\Support\Facades\Redis;

class SyncProgressTracker
{
    const REDIS_PROGRESS_KEY = 'videodb:sync:progress';
    const REDIS_LOCK_KEY = 'videodb:sync:lock';
    const TTL = 86400; // 24 hours

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
        ], $metadata);

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

        return $data;
    }
}
