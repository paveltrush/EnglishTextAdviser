<?php

namespace App\Logic\Repositories\Cache;

interface CacheRepository
{
    public function checkUserExists(string $id): bool;

    public function setState(string $id, string $state);

    public function setOption(string $id, string $option);

    public function getCurrentState(string $id): ?string;

    public function getCurrentOption(string $id): ?string;

    public function removeState(string $id);

    public function removeOptions(string $id);
}
