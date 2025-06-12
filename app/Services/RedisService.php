<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisService
{
    private const QUEUE_KEY = 'csv_queue';
    private const TTL = 3600;

    public function isAvailable(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function pushToQueue(string $taskId, string $filename): void
    {
        Redis::rpush(self::QUEUE_KEY, json_encode([
            'task_id' => $taskId,
            'filename' => $filename
        ]));
    }

    public function popFromQueue(): ?array
    {
        $task = Redis::blpop(self::QUEUE_KEY, 5);
        if (!$task) {
            return null;
        }
        return json_decode($task[1], true);
    }

    public function setTaskStatus(string $taskId, array|string $status, ?int $ttl = null): void
    {
        $data = is_array($status) ? $status : ['status' => $status];
        Redis::setex(
            "task_status:{$taskId}",
            $ttl ?? self::TTL,
            json_encode($data)
        );
    }

    public function getTaskStatus(string $taskId): ?array
    {
        $status = Redis::get("task_status:{$taskId}");
        return $status ? json_decode($status, true) : null;
    }

    public function setTaskResult(string $taskId, array $result): void
    {
        $result['status'] = 'completed';
        Redis::setex(
            "task_status:{$taskId}",
            self::TTL,
            json_encode($result)
        );
    }

    public function setBatch(string $key, array $batch): void
    {
        Redis::setex(
            $key,
            self::TTL,
            json_encode($batch)
        );
    }

    public function getBatch(string $key): ?array
    {
        $batch = Redis::get($key);
        return $batch ? json_decode($batch, true) : null;
    }

    public function clearTaskData(string $taskId): void
    {
        $keys = Redis::keys("csv_data:{$taskId}:*");
        if (!empty($keys)) {
            Redis::del($keys);
        }
        Redis::del("task_status:{$taskId}");
    }
} 