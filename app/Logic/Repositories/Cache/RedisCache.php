<?php

namespace App\Logic\Repositories\Cache;

use Illuminate\Support\Facades\Redis;

class RedisCache implements CacheRepository
{
    public function checkUserExists(string $id): bool
    {
        return Redis::exists("'user:' . $id . '.state'");
    }

    public function setState(string $id, string $state)
    {
        Redis::set("'user:' . $id . '.state'", $state);
    }

    public function setOption(string $id, string $option)
    {
        Redis::set("'user:' . $id . '.option'", $option);
    }

    public function getCurrentState(string $id): ?string
    {
        return Redis::get("'user:' . $id . '.state'");
    }

    public function getCurrentOption(string $id): ?string
    {
        return Redis::get("'user:' . $id . '.option'");
    }

    public function removeState(string $id)
    {
        Redis::del("'user:' . $id . '.state'");
    }

    public function removeOptions(string $id)
    {
        Redis::del("'user:' . $id . '.option'");
    }
}
